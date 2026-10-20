# C. Code Review

Two extracts from the existing codebase. For each: the problems, what each means in a clinic, a rank, and the three I would block a merge on. Then one refactor.

## How I ranked

- **Critical:** harms patients, exposes their data, or takes the service down.
- **Major:** gives staff wrong or missing information, or locks some staff out.
- **Minor:** makes the code harder to change safely, but nobody is hurt today.

## C1. PatientService (back end)

| # | Problem | What it means in a clinic | Rank |
| --- | --- | --- | --- |
| 1 | **SQL built by gluing user input into the query** (`Search`, `SaveEncounter`) | Anyone who can type in the search box can read, change or delete any patient record in the country. A note containing an apostrophe (Ng\`andu, "patient's") also simply fails to save | Critical |
| 2 | **Database password in the source code, using the `sa` account** | Everyone with access to the repository has full control of the national database, and the password is in every copy and backup of the code forever. `sa` means no limit on what a leak can do | Critical |
| 3 | **`SaveEncounter` swallows every error** (`catch { // ignore }`) | The clinician sees the visit as saved when it was not. Nobody finds out until the patient comes back and there is no record. Silent loss of clinical data | Critical |
| 4 | **The two writes in `SaveEncounter` are not in one transaction** | If the second statement fails, the visit exists but the patient's last visit date is wrong, so the follow-up list is wrong for that patient | Critical, with 3 |
| 5 | **The national ID of the first result is written to the log**, along with the search text | Personal data protected by law ends up in log files that many more people can read, and logs are kept and copied far more loosely than the database | Critical |
| 6 | **`userRole` is passed in but never checked**, and the facility comes from the caller | The method trusts whatever facility the caller sends and never looks at the role. A clinician at Mwansa can send FAC-0207 and read Kalemba's patients, and every role gets the same rights. Permissions exist on paper only | Major |
| 7 | **`DueForFollowUp` loads every patient, then their visits one patient at a time** | For a facility with 10,000 patients, that is 10,001 database calls for one screen. Slow for that clinic, and at weekday peak it slows the database for everyone | Major |
| 8 | **`DueForFollowUp` applies the wrong rule.** It adds a patient for every past appointment, not just the latest, and ignores visits since | Patients who came back are still listed, some appear several times, and staff phone people who already attended | Major |
| 9 | **Connections and readers are never closed** | Under load the connection pool runs out and the whole service stops answering, typically on a busy morning | Critical |
| 10 | `DateTime.Now` uses the server's clock and time zone | Around midnight the list is a day out for a clinic in a different time zone from the server | Minor |
| 11 | `SELECT *` and a leading `%` in `LIKE` | Returns more data than needed and cannot use an index, so search gets slower as the register grows | Minor |
| 12 | Static connection string, no dependency injection | Cannot point tests at a test database, so this code is effectively untestable | Minor |

**The three I would block the merge on:** 1 (SQL injection), 2 (credentials in source), and 3 with 4 (silent, partial saves). Each one on its own is a reportable incident. I would not let 5 or 9 through either. Number 9 takes the whole service down at peak, which is why I ranked it critical. Both are fixed inside the refactor below: 5 by one changed log line, 9 by the same using statements.

## C2. FollowUpList (front end)

| # | Problem | What it means in a clinic | Rank |
| --- | --- | --- | --- |
| 1 | **The whole list is refetched every 3 seconds, by every open screen** | With 3,000 staff at peak, that is roughly 1,000 requests a second, each returning up to 10,000 patients. It would take down the national service on its first busy morning, and it burns the mobile data of clinics on paid connections | Critical |
| 2 | **The response is treated as an array, but the API returns a page object** (`{ page, total, items }`) | `patients.sort` and `patients.filter` fail on the first load and the screen goes blank. Nothing tells the user why | Critical |
| 3 | **No error handling on any request.** On failure, `loading` stays true forever | When the network drops, which happens daily at 800 facilities, staff see "loading" indefinitely, with no message and no way to retry | Major |
| 4 | **"Mark contacted" is fire and forget.** No body, no error handling, no protection against double clicks | The contract needs `contacted_at`, so the call fails, but the row already shows as contacted. Staff believe the call was recorded when it was not. Double clicks record it twice | Major |
| 5 | **"Mark contacted" is a `div`, not a button** | Staff who work with the keyboard only, which the brief says many do, cannot use it at all. Screen readers do not announce it | Major |
| 6 | **Overdue is shown by red text only** | Staff with colour blindness cannot tell overdue from not overdue, and nothing says why a row matters | Major |
| 7 | **The filter box has no label, only placeholder text** | Screen readers announce an unnamed field. The placeholder vanishes once you type | Major |
| 8 | **Facility is hard-coded to FAC-0101**, and sent as `facility` when the contract says `facility_id` | Every clinic sees Mwansa Urban Clinic's patients, or the filter is ignored entirely. Either way staff act on the wrong list | Major |
| 9 | **The sort effect changes state in place**, then sets the same object back | React does not see a change, so the order shown depends on when something else re-renders. The list can appear unsorted | Major |
| 10 | No authorization header | Every request is rejected once authentication is on | Major |
| 11 | Changing facility does not cancel the previous request | A slow answer for the old facility can arrive last and overwrite the new one | Minor |
| 12 | Rows keyed by array index | After a re-sort React reuses the wrong rows. Harmless now, wrong as soon as a row holds any state | Minor |
| 13 | `any` everywhere, and `fetch` inside the component | A change to the API contract breaks the screen at runtime rather than at build time, and nothing can be tested without a live server | Minor |
| 14 | No empty state | A clinic with nobody to follow up sees a blank page and cannot tell "nobody" from "broken" | Minor |

**The three I would block the merge on:** 1 (polling that would take the national service down), 2 with 3 (a screen that crashes, or spins forever, with no explanation), and 4 with 5 (a contact action that silently fails and that keyboard users cannot reach).

My Section B screen is written as the answer to this list: paged requests and no polling, a service layer with typed responses, loading, empty and error states with retry, a real button with a retry-safe key, and status shown as text.

## Refactor: C1, PatientService

**Why C1 and not C2.** C1's worst problems cannot be undone after the fact. A leaked database or a lost visit stays leaked or lost. C2's worst problems are serious but visible, so staff notice and report them. Given one refactor, I would take the code that fails quietly and permanently. C2's fixes are also shown in my Section B screen.

**What this refactor fixes:** SQL injection (1), credentials in source (2), silent and partial saves (3, 4), and the national ID in the log (5). I have also closed connections (9), because the fix is the same `using` statements.

**What it deliberately leaves alone:** authorization (6), which belongs in the layer that knows who the user is, not in a query method; and `DueForFollowUp` (7, 8), which needs a rewrite rather than a fix. Section B shows that rewrite as one SQL query that applies the rule in the database.

## The refactored code

```csharp
public class PatientService
{
    private readonly string _connectionString;
    private readonly ILogger<PatientService> _log;

    // The connection string comes from configuration or a secret store, never from
    // source, and uses an account that can only read and write CareLink's own tables.
    public PatientService(IOptions<DatabaseOptions> database, ILogger<PatientService> log)
    {
        _connectionString = database.Value.ConnectionString;
        _log = log;
    }

    public List<Patient> Search(string q, string facilityId, string userRole)
    {
        // Values travel as parameters, so whatever is typed is only ever data.
        const string sql = @"
            SELECT id, facility_id, patient_number, first_name, last_name, date_of_birth, sex
            FROM patient
            WHERE facility_id = @facilityId
              AND (first_name LIKE @pattern OR last_name LIKE @pattern)";

        using var conn = new SqlConnection(_connectionString);
        using var cmd = new SqlCommand(sql, conn);
        cmd.Parameters.Add("@facilityId", SqlDbType.VarChar, 12).Value = facilityId;
        cmd.Parameters.Add("@pattern", SqlDbType.NVarChar, 102).Value = "%" + EscapeLike(q ?? "") + "%";

        conn.Open();
        var results = new List<Patient>();
        using (var reader = cmd.ExecuteReader())
        {
            while (reader.Read()) results.Add(Map(reader));
        }

        // Who searched and how many results. Never the search text, never an identifier.
        _log.LogInformation("Patient search by {Role} at {FacilityId} returned {Count} results",
            userRole, facilityId, results.Count);
        return results;
    }

    public void SaveEncounter(Encounter e)
    {
        using var conn = new SqlConnection(_connectionString);
        conn.Open();
        // Both writes succeed together or not at all.
        using var tx = conn.BeginTransaction();
        try
        {
            using (var insert = new SqlCommand(
                "INSERT INTO encounter (patient_id, visit_date, notes) VALUES (@patientId, @visitDate, @notes)", conn, tx))
            {
                insert.Parameters.Add("@patientId", SqlDbType.BigInt).Value = e.PatientId;
                insert.Parameters.Add("@visitDate", SqlDbType.Date).Value = e.VisitDate;
                insert.Parameters.Add("@notes", SqlDbType.NVarChar, -1).Value = (object?)e.Notes ?? DBNull.Value;
                insert.ExecuteNonQuery();
            }

            // Only move last_visit_date forward. A visit recorded late, for example
            // from a clinic that was offline, must not overwrite a newer one.
            using (var update = new SqlCommand(
                @"UPDATE patient SET last_visit_date = @visitDate
                  WHERE id = @patientId AND (last_visit_date IS NULL OR last_visit_date < @visitDate)", conn, tx))
            {
                update.Parameters.Add("@patientId", SqlDbType.BigInt).Value = e.PatientId;
                update.Parameters.Add("@visitDate", SqlDbType.Date).Value = e.VisitDate;
                update.ExecuteNonQuery();
            }

            tx.Commit();
        }
        catch (Exception ex)
        {
            tx.Rollback();
            // Logged with the patient's internal id only. The notes are never logged.
            _log.LogError(ex, "Saving an encounter for patient {PatientId} failed and was rolled back", e.PatientId);
            throw; // The caller must know the visit was not saved, so the clinician can be told.
        }
    }

    // In SQL Server LIKE, %, _ and [ are wildcards. Escape them so a search for "50%" means 50%.
    private static string EscapeLike(string value) =>
        value.Replace("[", "[[]").Replace("%", "[%]").Replace("_", "[_]");

    // Map(reader) and GetVisits(...) unchanged.
}
```

## How I would know it worked

- A test that saves a note containing `'); DROP TABLE patient; --` and reads it back unchanged.
- A test that makes the second statement fail, then checks that no encounter row was left behind and that the caller received the error.
- A secret scanner in the build pipeline, so a password committed again stops the build.
- Outside the code, the `sa` password is changed today. It has been in the repository, so it must be treated as already leaked.
