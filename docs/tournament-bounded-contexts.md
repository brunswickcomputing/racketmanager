### Defining Bounded Contexts for Tournaments

In Domain-Driven Design (DDD), a **Bounded Context** is a boundary within which a particular domain model is defined and applicable. For the "Tournaments" module in Racketmanager, the term "Tournament" currently means too many things at once (it's a financial entity, a schedule, a list of players, and a configuration).

Based on my analysis of `Tournament_Service`, `Competition_Entry_Service`, and `Finance_Service`, here is how we should define the Bounded Contexts for Tournaments:

#### 1. Tournament Configuration Context (The "Core")
This context handles the definition and lifecycle of a tournament.
*   **Focus:** When it starts, where it is, what the rules are, and its current phase (Open, In Progress, Closed).
*   **Aggregate Root:** `Tournament`
*   **Key Entities/Value Objects:** `Venue`, `TournamentGrade`, `TournamentSchedule` (the overall dates).
*   **Ubiquitous Language:** *Grade, Closing Date, Venue, Phase.*

#### 2. Registration & Eligibility Context
This context handles the process of players entering the tournament. It is distinct because an "Entry" has its own complex lifecycle (Pending, Partner Confirmed, Withdrawn).
*   **Focus:** Who is playing, who are their partners, and are they eligible to play in this specific event?
*   **Aggregate Root:** `Entry` (or `Registration`)
*   **Key Entities/Value Objects:** `Participant`, `PartnerLink`, `EligibilityRule`.
*   **Ubiquitous Language:** *Entry, Withdrawal, Partner, Seed.*

#### 3. Draw & Scheduling Context (The "Competition")
Once registration closes, the tournament enters the Draw phase. This context manages the brackets and the actual match-ups.
*   **Focus:** Creating the bracket (knockout, round-robin), assigning times to courts, and managing match outcomes.
*   **Aggregate Root:** `Draw` (or `Bracket`)
*   **Key Entities/Value Objects:** `Match` (as a slot in a bracket), `Round`, `CourtAssignment`.
*   **Ubiquitous Language:** *Draw, Round, Bye, Walkover, Progression.*

#### 4. Tournament Financials Context
Tournament fees and payments are distinct from the sporting logic. A player might be in the Draw but have an outstanding payment.
*   **Focus:** How much does it cost? Has the player paid? Generating invoices and tracking transaction status.
*   **Aggregate Root:** `TournamentPayment` (or `Invoice`)
*   **Key Entities/Value Objects:** `FeeStructure`, `Transaction`, `PaymentStatus`.
*   **Ubiquitous Language:** *Entry Fee, Paid, Invoice, Refund.*

---

### Why separate these?

1.  **Independent Scaling:** You can change how payments work (e.g., adding Stripe) without touching the logic that generates a knockout bracket.
2.  **Model Clarity:** In the **Registration Context**, a "Player" is an applicant. In the **Draw Context**, a "Player" is a "Competitor" in a specific slot. In the **Financial Context**, they are a "Billable Party."
3.  **Reduced Side Effects:** Currently, saving a result might trigger a financial check. In a DDD approach, the **Draw Context** would publish a `MatchCompleted` event, and the **Financial Context** would listen for it if it needs to trigger any payment-related logic.

### Proposed Next Steps for Tournaments

1.  **Extract `Entry` Logic:** Move the 1,200+ lines of entry logic in `Competition_Entry_Service` into a dedicated `Registration` sub-namespace.
2.  **Separate the "Draw":** Create a `Draw` aggregate root that owns the matches for a tournament, moving them out of the generic `Racketmanager_Match` if they are tournament-specific.
3.  **Event-Driven Invoicing:** Ensure `Finance_Service` reacts to tournament events (like `EntryConfirmed`) rather than the `Tournament_Service` calling finance methods directly.
