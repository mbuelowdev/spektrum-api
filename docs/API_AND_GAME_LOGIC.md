# Spektrum API and game logic

This document describes the HTTP API exposed by this Symfony application and how the game state machine and scoring work. Replace `HOST` with your API base URL (for local development this is often `http://localhost:3000` or the port your PHP server uses).

All request bodies below are JSON with `Content-Type: application/json` unless noted.

---

## Real-time updates (Mercure)

Clients can subscribe to room changes instead of polling `GET /room/{uuid}`.

- **URL:** `HOST/.well-known/mercure`
- **Topic:** `room-{ROOM_UUID}` (for example `room-019b5f8e-1234-7000-8000-abcdefabcdef`)

The hub publishes the **serialized room entity** as JSON—the same payload you get from `GET /room/{uuid}`—whenever the room changes (join, switch team, game actions, refresh, etc.).

Mercure typically requires a JWT or cookie for subscribing, depending on your deployment; see Symfony Mercure documentation and `config/packages/mercure.yaml`.

---

## Player lifecycle

### `POST /player/create`

Creates a player record.

**Body:**


| Field  | Type   | Required |
| ------ | ------ | -------- |
| `name` | string | yes      |


**Response (example):**

```json
{
  "message": "Successfully created player!",
  "uuid": "<PLAYER_UUID>"
}
```

Store `uuid` on the client; it identifies the player in all subsequent calls.

---

### `POST /player/{uuid}/heartbeat`

Updates the player’s `lastHeartbeat` timestamp. Call this on an interval (for example every **15 seconds**) so the server (and future admin tools) know the client is still connected.

**Response (example):**

```json
{ "message": "Heartbeat received!" }
```

If the player does not exist, the handler returns an empty JSON array `[]`.

---

## Room lifecycle

### `POST /room/create`

Creates a room. The **first player to create the room is treated as “admin” in the UI** by convention; the API does **not** store an admin flag—clients track that (for example “I created this room” in local storage).

**Body:**


| Field      | Type   | Required |
| ---------- | ------ | -------- |
| `password` | string | no       |


**Response:** JSON representation of the new `Room` entity (includes `uuid`, `password` is stored server-side but may not appear in all serializers—verify in your environment).

---

### `POST /room/join`

Adds a player to the room.

**Body:**


| Field        | Type   | Required                                 |
| ------------ | ------ | ---------------------------------------- |
| `uuidRoom`   | string | yes                                      |
| `uuidPlayer` | string | yes                                      |
| `password`   | string | no (required if the room has a password) |


**Behavior:**

- If the player is already in the room: `{ "message": "Already in the room." }`.
- If the password is wrong: `{ "message": "Failed to join the room. Wrong password." }`.
- On success, the player is added to **team A** initially (`addPlayersTeamA`). Clients then use **switch-team** to balance teams.

A Mercure update is published for the room.

---

### `POST /room/switch-team`

Moves a player between team A and team B.

**Body:**


| Field        | Type   | Required |
| ------------ | ------ | -------- |
| `uuidRoom`   | string | yes      |
| `uuidPlayer` | string | yes      |
| `team`       | string | yes      |


`team` must be `"A"` or `"B"`.

**Restrictions:**

- The player must already be in the room.
- If `gameState` is already set (game has started), the API returns `{ "message": "Game has already started." }` and does not change teams.

---

### `GET /room/{uuid}`

Returns the full **Room** JSON: teams, game state, points, active player, active card, target degree, clue text, guesses, etc. This is the canonical snapshot clients should render (and matches Mercure payloads).

---

### `POST /room/{uuid}/refresh`

Intended for the **room admin** in the UI. Republishes a Mercure update and persists the room.

**Note:** Logic that would remove players whose heartbeat timed out is **present in the codebase but commented out** in `RoomController`; until that is enabled, refresh does **not** clear inactive players by itself—it mainly triggers subscribers to re-fetch behavior via the published update path.

---

## Cards (reference data)

### `GET /cards`

Returns all spectrum cards (id and left/right labels). Gameplay uses whatever card is set as `gameActiveCard` on the room.

---

## Game actions

### `POST /room/{uuid}/game-action`

Executes one game action. **Every** action uses the same JSON shape:


| Field        | Type   | Required                   |
| ------------ | ------ | -------------------------- |
| `action`     | string | yes                        |
| `uuidPlayer` | string | yes                        |
| `value`      | string | yes (validator: not blank) |


Many actions ignore `uuidPlayer` and/or `value` on the server. Clients should still send valid strings (for example the acting player’s UUID and `"0"` or `"-"` for unused `value`) so validation passes.

**Response (success):**

```json
{ "message": "Successfully executed game action." }
```

After a successful action, the room is flushed and a Mercure update is published.

### Action constants (`action` field)

Defined in `App\Dto\GameActions`:


| Value                   | Meaning                                                |
| ----------------------- | ------------------------------------------------------ |
| `CREATE_NEW_GAME`       | Start or reset the game                                |
| `NEW_CARDS`             | Draw a new unplayed card (optional rules below)        |
| `NEW_OR_OLD_CARDS`      | Draw a new card, allowing already-played cards         |
| `START_SPINNING`        | Pick random target degree and advance state            |
| `SUBMIT_CLUEGIVER_CLUE` | Cluegiver submits sentence/clue (`value`)              |
| `SUBMIT_PREVIEW_GUESS`  | Place or update a **preview** guess (`value` = degree) |
| `REMOVE_PREVIEW_GUESS`  | Remove preview guesses for `uuidPlayer`                |
| `SUBMIT_GUESS`          | Lock in a non-preview guess (`value` = degree)         |
| `REVEAL`                | Force reveal from counter-guess state                  |
| `NEXT_ROUND`            | Advance to next round after reveal                     |


(`JOIN_ROOM` / `LEAVE_ROOM` exist in the DTO class but are **not** handled by `executeGameAction`; joining/leaving use the dedicated room endpoints.)

---

## Game states (`gameState` on Room)


| State                          | Typical meaning                                    |
| ------------------------------ | -------------------------------------------------- |
| `STATE_00_START`               | Active player may change card / start spinning     |
| `STATE_01_SHOW_HIDDEN_VALUE`   | Target degree is set; only cluegiver submits clue  |
| `STATE_02_GUESS_ROUND`         | Guessing team places preview/final guesses         |
| `STATE_03_COUNTER_GUESS_ROUND` | Counter-guessing team places preview/final guesses |
| `STATE_04_REVEAL`              | Points updated; active player may start next round |


Transitions are enforced in `GameLogicModel`; invalid actions no-op (no error body—check room state via GET/Mercure).

---

## Typical flow (aligned with product UX)

1. **Players** → `POST /player/create`.
2. **Room** → `POST /room/create` (creator acts as admin in the client).
3. **Join** → `POST /room/join`.
4. **Teams** → `POST /room/switch-team` until everyone is on A or B.
5. **Start game** → `game-action` with `CREATE_NEW_GAME`. Requires **both teams non-empty**. Resets played cards and guesses, sets round index to `0`, picks first **active player** from team A and an active card.
6. `**STATE_00_START`:** Active player may call `NEW_CARDS`, `NEW_OR_OLD_CARDS`, then `START_SPINNING`.
7. `**START_SPINNING`:** Server sets `gameTargetDegree` to a random value in **0–160** (steps of 0.01°), modeling a usable arc after margins. State becomes `STATE_01_SHOW_HIDDEN_VALUE`.
8. **Clue** → `SUBMIT_CLUEGIVER_CLUE` with the clue in `value`. State becomes `STATE_02_GUESS_ROUND`.
9. **Guessing team:** Players use `SUBMIT_PREVIEW_GUESS` / `REMOVE_PREVIEW_GUESS` / `SUBMIT_GUESS`. The **cluegiver** (`gameActivePlayer`) cannot submit guesses. Spectators (not on A or B) may only use **preview** guesses, not final ones.
10. When every **non–active-player** member of the guessing team has a **final** guess, state advances to `STATE_03_COUNTER_GUESS_ROUND`.
11. **Counter team** repeats preview/final guesses. When all required final guesses are in—or an authorized client sends `**REVEAL`**—the round resolves (see below).
12. `**STATE_04_REVEAL`:** Points have been applied. `**NEXT_ROUND`** picks the next active player and card and returns to `STATE_00_START`.

### Which team guesses when

- **Even** `gameRoundIndex` (0, 2, …): **Team A** is the guessing team first; **Team B** counters.
- **Odd** `gameRoundIndex`: **Team B** guesses first; **Team A** counters.

How many final guesses are required:

- In `**STATE_02_GUESS_ROUND`:** all teammates **except the cluegiver** (so `|team| - 1`).
- In `**STATE_03_COUNTER_GUESS_ROUND`:** all players on the counter team.

---

## Scoring (on reveal)

Implemented in `GameLogicModel::applyRevealScoring`:

1. `**gameTargetDegree`** is the bullseye.
2. **Guessing team** earns **tier points** from the **average** of their non-preview guesses vs the target (single tier, **not** stacked):
  - distance ≤ **4.5°** → **4** points  
  - ≤ **13.5°** → **3** points  
  - ≤ **22.5°** → **2** points  
  - otherwise **0** points  
   (“Distance” is `|averageGuess − target|`.)
3. **Counter team** can earn **1** bonus point if their average is closer to the target than the guessing team’s average; otherwise **0**.
4. If the guessing team has **no** final guesses, guessing points are **0**. If the counter team has **no** final guesses, their average is treated as **80.0** for the “who is closer” comparison only.

Points are added to `gamePointsTeamA` / `gamePointsTeamB`.

### Initial points on new game

On `CREATE_NEW_GAME`, the server sets **team A to 0** and **team B to 1** before the first round. That matches the current backend implementation (not symmetric zero-zero).

---

## Win condition (> 10 points)

The backend **does not** automatically stop the match when a score exceeds 10 or declare a winner. `**wins` on Player is not updated by this flow.** Clients should implement “game over” UX by comparing `gamePointsTeamA` / `gamePointsTeamB` to your house rules (for example first team **above** 10 points wins, tie-break by higher score).

---

## Privacy note: `gameTargetDegree`

The room JSON **includes** `gameTargetDegree` for every subscriber. **Hiding the dial from everyone except the active player is a client responsibility** (do not render it unless the local player is `gameActivePlayer`).

---

## Summary table of HTTP routes


| Method | Path                       | Purpose                     |
| ------ | -------------------------- | --------------------------- |
| POST   | `/player/create`           | Create player               |
| POST   | `/player/{uuid}/heartbeat` | Heartbeat                   |
| POST   | `/room/create`             | Create room                 |
| POST   | `/room/join`               | Join room                   |
| POST   | `/room/switch-team`        | Move player to team A or B  |
| GET    | `/room/{uuid}`             | Room snapshot               |
| POST   | `/room/{uuid}/refresh`     | Admin refresh / push update |
| POST   | `/room/{uuid}/game-action` | Game actions                |
| GET    | `/cards`                   | List all cards              |


---

## Client checklist

- Subscribe to Mercure topic `room-{ROOM_UUID}` for live updates.
- Send **heartbeat every ~15s** to `/player/{PLAYER_UUID}/heartbeat`.
- Treat **room creator** as admin (local storage); wire **Refresh** → `POST /room/{uuid}/refresh` and **Create new game** → `CREATE_NEW_GAME` game-action.
- After reveal, **Next round** → `NEXT_ROUND` (from `STATE_04_REVEAL`).

