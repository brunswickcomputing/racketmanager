## Here’s a concrete PHP class map
I’d organize it around **shared competition concepts** with specialization by:
- **entrant type**: team vs player
- **format**: league/round-robin vs knockout

# Recommended folder shape text
```
src/php/
    Domain/
        Competition/
        Entrant/
        Fixture/
        Result/
        Scoring/
        Scheduling/
        Standings/
        Shared/
    Services/
        Competition/
        Fixture/
        Result/
        Scheduling/
        Progression/
        Standings/
    Repositories/
        Competition/
        Fixture/
        Result/
        Entrant/
```

If you want to stay closer to your current structure, you can flatten it a bit, but this is the cleanest long-term map.
 
# 1. Domain classes
These are the core business concepts.
## `Domain/Competition`
### `Competition.php`
Top-level competition aggregate/root concept.
```
<?php

namespace Racketmanager\Domain\Competition;

final class Competition
{
    // id, name, type, format, entrant type, settings
}
```

### `Competition_Type.php`
Could be enum or constants.

Values:
- `league`
- `cup`
- `tournament`
- `box_league`
### `Competition_Format.php`
Values:
- round_robin
- knockout
- group_and_knockout
### `Event.php`
Represents an event within a competition.

Leagues and tournaments both benefit from this.
### `Stage.php`
Neutral abstraction for:
- division
- draw
- bracket
- group stage
- box

Fields:
- id
- event id
- name
- stage type
### `Stage_Type.php`
Values:
- `division`
- `group`
- `draw`
- `bracket`
- `box`

### `Domain/Entrant`
This is the key abstraction.
### `Entrant.php`
Interface. 
```
<?php

namespace Racketmanager\Domain\Entrant;

interface Entrant
{
    public function id(): int|string;
    public function display_name(): string;
    public function type(): Entrant_Type;
}
```

### `Entrant_Type.php`
Values:
- team
- player
### `Team_Entrant.php`
Wraps a team as a competition entrant.
###`Player_Entrant.php`
Wraps a player as a competition entrant.
### `Entrant_Reference.php`
Small value object for storing entrant id/type together.

Useful in fixtures and repositories.
 
## `Domain/Fixture`
This is the replacement direction for the legacy giant match object.
### `Playable_Unit.php`
Interface for things that can be played and scored.

Implemented by:
- `Fixture`
- `Rubber`
### `Fixture.php`
The scheduled contest between two entrants.

Fields:
- id
- competition id
- event id
- stage id
- season
- round number / match day
- side A entrant
- side B entrant
- scheduled datetime
- status
- result id or embedded result reference

For team competitions:
- may contain rubber ids or loaded rubbers
### `Fixture_Type.php`
Optional, if useful:
- team_fixture
- player_fixture
### `Fixture_Status.php`
Values like:
- draft
- scheduled
- in_progress
- complete
- cancelled
- walkover
### `Fixture_Participant_Assignment.php`
Useful if you want to track who is assigned where.

For example:
- fixture side -> entrant
- rubber side -> player(s)
### `Rubber.php`
For team competitions only.

Fields:
- id
- fixture id
- rubber number
- side A player assignment
- side B player assignment
- result
- format/type
### `Rubber_Type.php`
Values:
- singles
- doubles
- maybe mixed_doubles if needed
 
## `Domain/Result`
### `Result.php`
Represents outcome and summary.

Fields:
- winner reference
- loser reference
- status
- confirmation status
- scoreline
- walkover/retired/default flags
### `Result_Status.php`
Values:
- pending
- confirmed
- walkover
- retired
- void
### `Fixture_Result.php`
Optional subclass/specialized class if fixture results diverge meaningfully.
### `Rubber_Result.php`
Optional if rubber results need separate logic.

If not, one Result class is enough.
 
## `Domain/Scoring`
### `Set_Score.php`
Tiny value object.

Fields:
- side A games
- side B games
- optional tiebreak scores
### `Scoreline.php`
Useful aggregate/value object.

For example:
- sets won
- games won
- points/rubbers summary
### `Side_Score.php`
Optional if you want a reusable scoring component.
 
## `Domain/Scheduling`
### `Round_Number.php`
Optional value object.
### `Match_Day.php`
Useful for leagues.
### `Scheduled_Date.php`
Optional if date handling needs normalization.
 
## `Domain/Standings`
Mostly for league/box competition behavior.
### `Standing_Row.php`
Represents one row in standings.
### `Standings_Table.php`
Collection/model of standings.
 
## `Domain/Shared`
### `Identifier.php`
Optional common VO if you want one.
### `Season.php`
Likely already exists or should exist.
### `Money.php`
Only if needed elsewhere; not core to this part.
 
# 2. Services
These orchestrate domain logic and side effects.
 
## `Services/Competition`
### `Competition_Factory.php`
Builds competition aggregate or specialized competition models.
### `Stage_Factory.php`
Builds stages/divisions/draws.
### `Entrant_Factory.php`
Creates Team_Entrant or Player_Entrant.
 
## `Services/Fixture`
### `Fixture_Factory.php`
Creates fixtures with correct entrant references and metadata.
### `Fixture_Assignment_Service.php`
Assigns entrants/teams/players to fixture sides.

Very useful when progression or draw placement happens.
### `Fixture_Result_Manager.php`
Updates a fixture result, validates transitions, triggers follow-up actions.

This is a major replacement candidate for parts of the old match class.
 
## `Services/Result`
### `Result_Manager.php`
Handles:
- confirming results
- recalculating winners/losers
- applying walkovers/defaults/retirements
### `Rubber_Result_Manager.php`
Updates rubber-level results.
### `Score_Validation_Service.php`
Validates set scores and scorelines.
 
## `Services/Scheduling`
### `League_Fixture_Scheduler.php`
Round-robin schedule generation.
### `Knockout_Fixture_Scheduler.php`
Generates knockout fixtures.
### `Box_League_Scheduler.php`
If box leagues behave differently enough.
 
## `Services/Progression`
### `Knockout_Progression_Service.php`
Advances winners from one fixture to the next.

This is where your current championship/cup progression logic should trend.
### `Championship_Factory.php`
Your current extracted class fits here conceptually.
### `Championship_Manager.php`
Also fits here, though over time it may become more specifically:
- `Knockout_Progression_Service`
- `Bracket_Assignment_Service`
 
## `Services/Standings`
### `Standings_Service.php`
Calculates standings from fixture results.
### `League_Table_Updater.php`
Could be a more explicit name if it performs mutations.
### `Box_Standings_Service.php`
Only if box league rules differ enough.
 
# 3. Repositories
These handle persistence access.
 
## `Repositories/Competition`
### `Competition_Repository.php`
Load/save competitions.
### `Event_Repository.php`
Load/save events.
### `Stage_Repository.php`
Load/save divisions/draws/groups/brackets.
 
## `Repositories/Entrant`
### `Entrant_Repository.php`
Generic lookup if needed.
### `Team_Entrant_Repository.php`
Load team entrants or team references.
### `Player_Entrant_Repository.php`
Load player entrants.
 
## `Repositories/Fixture`
### `Fixture_Repository.php`
Load/save fixtures.

Queries like:
- by stage
- by event
- by round
- by match day
- by entrant
### `Rubber_Repository.php`
Load/save rubbers for team fixtures.
 
## `Repositories/Result`
### `Result_Repository.php`
Persist results.
### `Set_Score_Repository.php`
Only if set scores are stored separately enough to justify it.

If not, keep set score persistence inside result repository.
 
# 4. Suggested class map by competition type
This is where the model becomes concrete.
 
## League competitions
### Domain
- `Competition`
- `Event`
- `Stage` as division
- `Team_Entrant`
- `Fixture`
- `Rubber`
- `Result`
- `Set_Score`
- `Standings_Table`
### Services
- `League_Fixture_Scheduler`
- `Standings_Service`
- `Fixture_Result_Manager`
- `Rubber_Result_Manager`
### Repositories
- `Fixture_Repository`
- `Rubber_Repository`
- `Result_Repository`
- `Stage_Repository`
 
## Cup competitions
### Domain
- `Competition`
- `Event`
- `Stage` `as bracket
- `Team_Entrant`
- `Fixture`
- `Rubber`
- `Result `
### Services
- `Knockout_Fixture_Scheduler`
- `Knockout_Progression_Service`
- `Fixture_Result_Manager`
- `Rubber_Result_Manager`
### Repositories
- same as leagues, minus standings-heavy pieces
 
## Tournament competitions
### Domain
- `Competition`
- `Event`
- `Stage` as draw
- `Player_Entrant`
- `Fixture`
- `Result`
- `Set_Score`

Usually no `Rubber` needed.
### Services
- `Knockout_Fixture_Scheduler`
- `Knockout_Progression_Service`
- `Fixture_Result_Manager`
### Repositories
- `Fixture_Repository`
- `Result_Repository`
- `Player_Entrant_Repository`
 
## Box leagues
### Domain
- `Competition`
- `Event`
- `Stage` as box/group
- `Player_Entrant`
- `Fixture`
- `Result`
- `Standings_Table`

Usually no `Rubber`.
### Services
- `Box_League_Scheduler`
- `Standings_Service`
- `Fixture_Result_Manager`
 
# 5. Recommended interfaces
These help without creating interface soup.
## `Entrant`
For team/player abstraction.
## `Playable_Unit`
Implemented by:
- `Fixture`
- `Rubber`

Useful if both can:
- have participants
- have results
- be completed
## `Resultable`
Optional if you want a smaller interface instead of Playable_Unit.
## `Schedulable`
Optional, but only if shared scheduling behavior emerges.

Don’t create interfaces just to look enterprisey. PHP can smell fear.
 
# 6. Practical first migration target from `Racketmanager_Match`
If you want a realistic incremental path, extract in this order:
## Phase 1
- `Fixture`
- `Result`
- `Rubber`
- `Set_Score`
## Phase 2
- `Fixture_Result_Manager`
- `Knockout_Progression_Service`
- `Standings_Service`
## Phase 3
repositories for fixture/result/rubber

This sequence gives the best payoff without a full rewrite explosion.
 
# 7. Concrete example namespace map
Here’s a very usable version.
```
src/php/Domain/
  Competition/
    Competition.php
    Competition_Format.php
    Competition_Type.php
    Event.php
    Stage.php
    Stage_Type.php

  Entrant/
    Entrant.php
    Entrant_Type.php
    Entrant_Reference.php
    Team_Entrant.php
    Player_Entrant.php

  Fixture/
    Playable_Unit.php
    Fixture.php
    Fixture_Status.php
    Fixture_Participant_Assignment.php
    Rubber.php
    Rubber_Type.php

  Result/
    Result.php
    Result_Status.php
    Scoreline.php

  Scoring/
    Set_Score.php

  Standings/
    Standing_Row.php
    Standings_Table.php

src/php/Services/
  Competition/
    Competition_Factory.php
    Stage_Factory.php
    Entrant_Factory.php

  Fixture/
    Fixture_Factory.php
    Fixture_Assignment_Service.php
    Fixture_Result_Manager.php

  Result/
    Result_Manager.php
    Rubber_Result_Manager.php
    Score_Validation_Service.php

  Scheduling/
    League_Fixture_Scheduler.php
    Knockout_Fixture_Scheduler.php
    Box_League_Scheduler.php

  Progression/
    Knockout_Progression_Service.php
    Championship_Factory.php
    Championship_Manager.php

  Standings/
    Standings_Service.php
    League_Table_Updater.php

src/php/Repositories/
  Competition/
    Competition_Repository.php
    Event_Repository.php
    Stage_Repository.php

  Entrant/
    Entrant_Repository.php
    Team_Entrant_Repository.php
    Player_Entrant_Repository.php

  Fixture/
    Fixture_Repository.php
    Rubber_Repository.php

  Result/
    Result_Repository.php
```
 
# 8. Best minimal version if you want less structure now
If the full tree feels too big, start with this smaller map:
```
src/php/Domain/
-  Competition.php
-  Event.php
-  Stage.php
-  Entrant.php
-  Team_Entrant.php
-  Player_Entrant.php
-  Fixture.php
-  Rubber.php
-  Result.php
-  Set_Score.php

src/php/Services/
-  Fixture_Result_Manager.php
-  Knockout_Progression_Service.php
-  Standings_Service.php
-  Championship_Factory.php
-  Championship_Manager.php

src/php/Repositories/
  Fixture_Repository.php
  Result_Repository.php
  Rubber_Repository.php
```
That’s enough to start the migration without building a cathedral.
# 9. My recommendation for naming
If you want the most stable terminology:
- `Fixture` = scheduled contest
- `Rubber` = sub-contest inside team fixture
- `Result` = outcome
- `Set_Score` = atomic scoring
- `Entrant` = team or player competitor
- `Stage` = division/group/draw/bracket container

That naming will survive future changes much better than continuing to overload `Match`.
## Final recommendation
### Use this as your core model:
- `Competition`
- `Event`
- `Stage`
- `Entrant`
- `Fixture`
- `Rubber`
- `Result`
- `Set_Score`

### Put:
- core business objects in **Domain**
- orchestration/calculation in **Services**
- persistence in **Repositories**

### Most important migration path:
Move responsibilities into: `Racketmanager_Match`
1. `Fixture`
2. `Result`
3. `Rubber`
4. manager/services for mutations and progression
