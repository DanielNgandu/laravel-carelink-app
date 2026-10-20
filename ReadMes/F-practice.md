# F. Delivery and Engineering Practice: the first 90 days

By day 90: nobody changes production by hand, every change is reviewed and built automatically before it ships, a release takes under a day instead of three and does not need a hotfix, and programme teams and engineers work from one ordered backlog they both agreed. Clinical services carry on throughout, so none of this needs a delivery freeze. Each block keeps features flowing and changes how they are built, not whether they are built.

Underneath all of it sits one thing the team does not have today: a **CI/CD pipeline**, the automated route that moves a change from an engineer's computer to the clinics safely and quickly, through the same review, tests and checks every time. Block one establishes it, so every change is built and tested automatically. Block two extends it to a staging copy of the system and a rehearsed release. Block three makes it the only way into production.

The team: two senior engineers, five engineers, two junior developers, one QA engineer and one business analyst.

## At a glance

| | Days 1 to 30: make change safe | Days 31 to 60: make it repeatable | Days 61 to 90: pay down technical debt |
| --- | --- | --- | --- |
| Main changes | No hand changes to production; review and build on every change; two streams led by seniors; backlog sorted into 4 groups; weekly intake with programmes | Every change adds a test; automatic deploy to staging; weekly release with rollback; API docs built from code; debt register with costs | 20% of time on top debt; highest-cost debt first; debt agreed with programmes; release when ready; monthly measures review |
| Checkpoint before moving on | No manual production changes; every change reviewed and built | Releases take under a day | Four measures at target; team releases without me |

## The two changes I would make first

**1. Nobody changes production by hand. Week one.** Two engineers routinely apply fixes directly on the production server. Those changes are not reviewed, not tested, not in source control, and are overwritten by the next release, which is a likely source of the weekly defect. I would remove direct write access to production for everyone, including me, and replace it with an emergency route: a named on-call senior can still act at 2 a.m., but the action is logged, and the same fix goes in as a reviewed change the next working day.

*Why first:* it is the biggest risk to patient care, it costs almost nothing, and every other improvement is wasted while untracked changes can still land underneath it. I would explain it to the two engineers privately first. They have been keeping the service running, and the point is to protect them as well as the patients.

**2. Every change goes through a pull request, and the build runs on it. Weeks one and two.** One reviewer, a short checklist, and an automated build that runs the tests that already exist. The tests that fail for unrelated reasons are moved to a quarantine list, so a red build means something again, and that list is worked down in block two.

*Why second:* roughly one production defect a week traces back to a change from the previous fortnight. A second pair of eyes and a build that runs every time is the cheapest way to catch those before they ship, and it is the base everything else in this plan stands on: standards, tests and automated releases all arrive through this one door.

Everything else waits until these two are in place.

## Days 1 to 30: make change safe

**What changes**

- **Team structure.** Two streams, each led by a senior engineer. *Delivery* takes the work programme teams ask for. *Stability* takes defects, quarantined tests and release tooling. Engineers rotate between streams every month, so nobody is stuck on bugs and everyone sees the consequences of rushed work. The QA engineer joins refinement, so test cases are written before the code, not after it.
- **Code review and branching.** Production access and pull requests, as above. Short-lived branches off one main branch, merged within two or three days. No long-running feature branches, which are where three-day merges and surprise conflicts come from.
- **Coding standards.** Not written from scratch. The team adopts the standard style guide for the language, enforced by an automatic formatter and linter in the build, so review time goes on logic rather than layout. The seniors add a one-page list of house rules: parameterised SQL only, no secrets in code, no swallowed errors. These are the faults from my Section C review.
- **Backlog.** The business analyst and I sort the 300 items into four groups: defects affecting care, Ministry commitments with dates, technical debt, and ideas. Anything nobody can explain the value of in a sentence is closed, with a note that it can be reopened.
- **Programme team friction.** A 30-minute weekly intake with programme leads, run by the business analyst. New requests come in there, not through engineers' inboxes, and requirements change in that meeting, not halfway through a build.
- **Mentoring.** Each junior developer is paired with a senior for every change they ship this month.
- **Metrics.** Start counting, change nothing yet: release time, defects traced to recent changes, time to fix a production fault, and how often we release.

**What I leave alone:** no rewrite, no new framework, no change to reporting lines, no test coverage target, and no new tools beyond the build pipeline. The team has to keep shipping while the ground moves under it.

**How I will know it worked:** zero manual production changes outside the logged emergency route; every merged change has a review and a passing build; the backlog is in four labelled groups; and programme leads attend the weekly intake.

## Days 31 to 60: make it repeatable

**What changes**

- **Testing.** The rule becomes: every change adds or fixes a test for the code it touches. That raises coverage where the code actually changes, which is where the defects come from, rather than across code nobody edits. The Stability stream works the quarantined tests down to zero, either fixing or deleting each one. The QA engineer turns the five most important clinic journeys, such as recording a visit or opening the follow-up list, into automated end-to-end checks that run before every release.
- **CI/CD.** Every change that reaches main is deployed automatically to a staging copy of the system with realistic data. A release is the same build that passed staging, promoted, never rebuilt.
- **Release management.** A weekly release on a fixed day, outside the morning clinic peak, with a written checklist and a rehearsed rollback. A release that cannot go out on time waits a week; the train does not wait for it. Database changes follow the two-step rule from Section A, so a rollback never needs a database restore.
- **Documentation.** API documentation is generated from the code in the build, so it can no longer drift a year out of date. Each service gets a one-page runbook: how to deploy it, roll it back, and diagnose the three most common faults.
- **Backlog.** One ordered list, agreed monthly with programme leads, with the top 20 items properly written up and the rest only roughly sized. Programme teams see where their request sits and why.
- **Technical debt.** A debt register, each item with a cost in terms people outside engineering understand: "this adds two days to every change in the referral module", or "this caused three of last month's defects". Unquantified debt becomes a list someone can prioritise.

**What I leave alone:** no daily deployments yet, no new process framework with its own ceremonies, and no reorganisation. A weekly release that works every time is worth more than a faster one that sometimes does not.

**How I will know it worked:** releases take under a day; at least three releases in a row need no hotfix; the quarantine list is empty; and the API documentation matches the running code because it is built from it.

## Days 61 to 90: pay down technical debt

**What changes**

- **Technical debt.** A fixed share of each fortnight, around 20 percent, goes to the top of the debt register, agreed openly with programme leads rather than hidden inside feature estimates. Debt work is chosen by what it costs today, not by what engineers find untidy.
- **Release management.** With staging, end-to-end checks and rollback proven, releases move from weekly to whenever a change is ready and has passed, still outside peak hours, with features switched on separately from deployment so risky changes can go out dark.
- **Code review and mentoring.** Junior developers start reviewing seniors' changes, with the senior explaining their reasoning. It is the fastest way juniors learn the codebase, and it keeps seniors' code readable. Each engineer agrees one growth goal with me, tied to this plan: owning the runbook for a service, or leading the next release.
- **Metrics.** The four numbers below go to a monthly review with the team and programme leads, in plain terms. Programme teams see delivery speed and stability on the same page, which ends the argument about whether engineering is slow or requirements keep changing, because both become visible.
- **Retrospective.** A short team retrospective at day 90 decides what the next 90 days should change. The plan is mine to start, but the team's to continue.

**What I leave alone:** a coverage percentage target, which teams learn to game; a move to separate services; and any change of tracker or tooling the team already knows. None of those is the bottleneck.

## Measures

Four numbers, reviewed monthly with the team and programme leads. Where the brief gives no starting figure, block one measures it before anyone sets a target.

| Measure | Day 90 target |
| --- | --- |
| Time to release | Under 1 day, no hotfix |
| Changes that break something | 1 a month or fewer |
| Time to recover | Within one working day, rollback within an hour |
| How often we release | Weekly, then as often as ready |

Two more are tracked in block one only, because they should reach zero and stay there: manual changes made directly on production, and tests that fail for reasons unrelated to the change.

## Example: the technical debt register

| ID | The debt | What it costs today | The fix | Effort | Priority | Status |
| --- | --- | --- | --- | --- | --- | --- |
| TD-01 | Database password in source code, using the all-powerful `sa` account | Anyone with the code controls the national database. Every security review flags it, which holds up releases | Move to a secret store, use a limited account, change the password | 2 days | High | Not started |
| TD-02 | SQL built by gluing user input into queries | Patient data can be read or deleted through the search box. Notes containing an apostrophe fail to save | Parameterised queries throughout, with a test for each | 3 days | High | Not started |
| TD-03 | Saving a visit hides every error | Visits are lost without anyone knowing, found only when the patient returns | Save in one transaction, report failures to the user | 1 day | High | Not started |
| TD-04 | Follow-up list worked out in application memory, one patient at a time | About 10,000 database calls for one screen at a large facility, slowing everyone at peak | One database query, as in Section B | 5 days | Medium | Not started |
| TD-05 | Tests that fail for unrelated reasons | A red build is ignored, so real failures slip through. Engineers lose time re-running builds | Fix or delete each quarantined test | 5 days | Medium | Not started |
| TD-06 | API documentation a year out of date | Partners building integrations guess, and the laboratory link takes longer than it should | Generate the documentation from the code in every build | 3 days | Medium | Not started |
| TD-07 | Releases done by hand over three days | Three days of senior time per release, usually followed by a hotfix | Script the release and rehearse the rollback | 8 days | High | Not started |
| TD-08 | Inconsistent code style across modules | Slower reviews and harder onboarding for the juniors | Automatic formatter in the build; files tidied only when they are next changed | 1 day | Low | Not started |
