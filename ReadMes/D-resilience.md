# D. Resilience, Offline and Data Integrity

This builds on two decisions from Section A. Clinical facts are written as entries in an e-ledger/e-register that is only ever added to, and every entry gets its own reference number/interaction ID on the clinic computer before it is sent. Section B shows the second idea working: the contact endpoint accepts an `Idempotency-Key`, stores the request once, and answers 409 if the same key arrives with a different request.

---

## D1. Duplicate submissions

At 16:00 the connection returns, the client replays its queue, times out partway, and replays the whole queue again. Some visits are now recorded twice, and some patients were created twice.

### 1. Making a replay safe

**Every operation carries a reference number, or interaction ID, made by the client**, a random UUID created at the moment the clinician records the visit, not at the moment it is sent. The server treats that number as the identity of the operation.

- **Creating records.** The reference number is stored on the record itself, in a column with a unique constraint. Nothing new is written.
- **Creating patients.** The clinic computer gives each new patient their identifier when they are registered offline. Visits recorded afterwards point at that identifier, so they link to the right patient even before the server has seen either. A second "create patient" with the same identifier is the same patient, not a new one. Where a record came from (created online, created offline on this computer, or received through a transfer in) and whether it is still waiting to sync are kept as separate fields and shown on screen as labels. They are not built into the identifier, because sync status changes, and an identifier that changes breaks every visit that points at it. The readable patient number does carry the facility code and the code of the computer that registered the patient, for example 0101-03-000471, so two computers registering offline at the same facility can never issue the same number. And no one can be registered without a search first, online or offline. Staff search by name, date of birth and phone number, and the register button only appears after they confirm none of the results is this person. Online, the search covers the national register, so a patient registered at another facility is found and transferred rather than created again. Offline, it covers this facility's records, the screen says plainly that the national check could not run, and the new patient is marked for a national check. When they sync, the server runs that check and sends any likely match to a person to decide. That search is what actually stops the same person being registered twice.
- **Changing records.** Updates do not create a row to hold the reference, so the server keeps a small table of applied operations: the reference, a fingerprint of the request, and the result. The operation and its entry in this table are written in one transaction, so it is impossible to apply one without recording the other.

**What I rejected:** spotting duplicates by content, for example "same patient, same date, same visit type". Two genuine visits can look identical, such as a patient seen in the morning and again in the afternoon. Matching on content is a guess. A reference number is a fact.

### 2. What the client must do

- Create the reference once, when the action is recorded, and store it with the queued item.
- Send the same reference on every retry. Never make a new one.
- Keep the queue in durable storage, so a crash or a page reload does not lose items or give them new references.
- Remove an item only when the server has confirmed it, as either applied or already applied. Both count as success.
- Send items in the order they were recorded, so a patient is always created before their visits.

If the client breaks the second rule, the server cannot help it. That is why the rule lives in one service, the sync module, with tests that replay the same queue twice and check that nothing doubles.

### 3. How long the server keeps the references, and what happens after

**On created records: forever, at no extra cost.** The reference is a column on the visit or patient row, so it lives as long as the record. A visit can never be duplicated, however late the replay.

**In the applied-operations table: 13 months.** It must outlast the longest time an operation can sit in a queue before reaching the server. In Section A a hard-to-reach facility can work offline for up to 180 days, and work can travel by USB stick on a supervision visit after that. The API also accepts contact attempts up to a year old. Thirteen months covers all of that with a margin. The table is split by month, so removing a month is one cheap operation rather than millions of deletes.

**After 13 months**, the server can no longer prove an old update was not already applied. So it does not guess. An update older than the window is refused with a specific code, and the client moves it to the facility's list of cases needing a decision. A person looks at it. In practice this should almost never happen, because a facility silent for that long has been visited many times over.

### 4. The duplicates already in the database

1. **Stop it happening again first.** Release the reference numbers before cleaning anything, or the cleanup is undone by the next bad afternoon.
2. **Measure.** Count the candidates by facility so the Ministry hears a number, not "some".
3. **Visits.** A replayed visit is identical in every clinical field and was recorded by the same user, on the same computer, within the replay window. Where every one of those matches, the later copy is marked as *superseded by* the original. It stops appearing on lists and in reports, but stays in the record with a note of why. Anything less than an exact match goes to a person to decide.
4. **Patients.** Never merged automatically. Candidates are patients at the same facility with the same names, date of birth and sex, created minutes apart on the same computer. The facility administrator or the national data steward compares them side by side and chooses which record survives. The merge is itself a ledger entry: the duplicate is marked *merged into* the survivor, its visits follow, and its patient number is kept as an alias so the paper card still finds the right person. Because nothing was deleted, a wrong merge can be reversed.
5. **Correct the reports.** Duplicate visits will have inflated indicator totals already sent to the national reporting platform. The nightly send in Section A can be re-run for a period and overwrites rather than adds, so affected periods are re-sent once the cleanup is done.

---

## D2. Working through an outage

### 1. What the client keeps, where, and for how long

| What | Where | How long |
|---|---|---|
| The application itself | Browser cache, through the service worker. No patient data | Until the next version is downloaded |
| This facility's working set: active patients, their last 12 months of visits, the follow-up list, reference data | Browser database (IndexedDB), encrypted | Refreshed on every sync. Older data is dropped. Capped at 200 MB |
| Work waiting to upload | Same encrypted store, in its own reserved space | Until the server confirms it. Never dropped to make room. 300 MB reserved |
| The offline sign-in credential | Same store, signed by the server | 30 days by default, 180 for facilities marked hard to reach. Any contact with the server resets the clock |

A few hours offline and two days offline behave the same way. The staleness banner shows when the data was last refreshed, and nothing else changes for the user.

### 2. Sending the queue when the network returns

- **Order.** Oldest first, in batches of 50. A patient's creation always goes before their visits.
- **Retry and back-off.** A failed batch waits 2 seconds, then 4, 8 and so on, up to 5 minutes, with a random spread added. The spread matters: when a provincial link comes back at 08:00, hundreds of clinics would otherwise all retry in the same second.
- **Timeouts.** A batch that times out halfway is simply sent again. Because of the reference numbers, this is safe. This is the 16:00 scenario from D1, made harmless.
- **Partial failure.** Each operation in the reply is handled on its own:
  - *applied* or *already applied*: removed from the queue;
  - a temporary failure, such as a server error or timeout: stays in the queue and is retried;
  - a permanent refusal, such as access withdrawn, a patient since merged, or bad data: moved to the facility's list of cases needing a decision, with a plain explanation. It does not block the rest of the queue, except later operations for the same patient, which wait behind it.
- **Then download.** Once the upload is done, the client fetches what changed centrally since its last sync, including the user list, so revoked accounts stop working on that computer.

### 3. Monday, Tuesday, Wednesday

A clinician edits a record offline on Monday. The central record changes on Tuesday. They reconnect on Wednesday. What happens depends on what was edited.

**A clinical fact, such as a visit, a contact attempt or a note.** Both are kept. The ledger only adds, so there is nothing to conflict. The record shows Monday's entry and Tuesday's entry in date order.

**A field with one right answer, such as a phone number.** Every offline edit carries the version of the record it started from. On Wednesday the server compares:

- if nobody changed *that field* centrally since Monday's version, Monday's edit is applied, even if other fields changed;
- if Tuesday changed the same field to something else, neither value silently wins. Tuesday's value stays, and Monday's goes to the facility's list of cases needing a decision.

I rejected "latest timestamp wins" here. Monday's edit reaches the server on Wednesday, after Tuesday's, so judging by arrival time would let the older edit overwrite the newer one, and judging by the clinic's clock trusts a clock nobody controls.

**What the user sees.** Until Wednesday, Monday's edit shows on that computer marked *waiting to upload*. After the sync, a quiet notice says *1 change needs a decision*. Opening it shows both values side by side, with who changed each and when.

**Who decides.** The facility administrator, because the facility owns the patient and is closest to the truth, for example by phoning the patient to check which number works. If Tuesday's change came from the national data steward as a maintenance correction, it stands until the facility disputes it. In neither case does the software decide alone.

### 4. Protecting data on a shared clinic computer

**How it is protected:**

- Everything stored locally is encrypted in the browser. The key is unlocked only by a successful sign-in, online or with the offline PIN, and is locked again after 10 minutes of inactivity.
- Every member of staff has their own account and PIN. No shared logins, so every action is attributable.
- Only the minimum is held: this facility, active patients, 12 months of visits.
- The offline credential expires, and a computer reported lost is wiped the next time it connects.
- Nothing personal goes into browser local storage, page addresses, the application cache or the logs.
- The USB export used by hard-to-reach facilities is encrypted so that only the server can read it. A lost stick reveals nothing.

**What I would refuse to store on the clinic computer at all:**

- history older than 12 months;
- passwords or long-lived tokens in readable form;
- any export into the downloads folder, other than the encrypted USB queue file.
