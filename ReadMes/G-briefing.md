# Briefing: new mandatory reporting field

**Ask:** approve a phased rollout. The figure reaches the Ministry within two weeks on the existing monthly return, and is in CareLink at all 1,500 facilities by week eight.

## Why not two weeks

Three tasks, none optional:

1. Change every facility's database to hold the field.
2. Change the data format sent to the server, keeping older software working.
3. Security review of the new connection carrying health data. About two weeks; skipping it risks a legal breach.

Build and test take about three weeks for nine engineers and one QA engineer, who also run CareLink daily. One QA engineer is the bottleneck.

## Reaching facilities is the bigger constraint

| Group | Facilities | How updated | Time per facility | Live by |
|---|---|---|---|---|
| Reliable internet | 400 | Pushed remotely | 15 minutes, unattended | Week 3 |
| Intermittent internet | 800 | Remotely, confirmed by a support call | 1 hour of support desk time | Week 6 |
| Often offline | 300 | District officer visit with a USB stick | 1 officer-day with travel | Week 8 |

That is 300 officer-days of travel, fuel and allowances: five weeks with 30 officers giving two days a week.

## Options

| Option | Consequence |
|---|---|
| 1. Everything in two weeks | Not achievable: the visits alone take five weeks. Forcing it cuts testing and the security review |
| 2. Wait until everything is ready | Lowest risk, but no new data for two months |
| 3. Phased (recommended) | Figure on the monthly return from week 2; in CareLink everywhere by week 8. No facility stops reporting |

## Risks in option 3

- Field definition changes after build starts: about a week per change.
- Support desk overload: updates go out in daily batches of 60.
- Officers or transport unavailable: offline facilities slip past week eight.
- Two reporting routes for up to six weeks: reconciled by facility code to avoid double counting.

## Decisions needed this week

1. Approve the phased plan.
2. Fix the field definition by day three.
3. Fund transport and allowances for about 300 district officer visits.
4. Collect the figure on the monthly return from week two until each facility is updated.

*Times and officer numbers are estimates to confirm with provinces.*
