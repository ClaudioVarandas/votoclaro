1.
🖥 Dashboard Wireframe

```shell
-------------------------------------------------
| VotoClaro Logo        | Navigation            |
-------------------------------------------------

|  Total Initiatives  |  Government Approval  |
|        142          |        78% Approved   |

|  🟢 Approved  |  🔴 Rejected  |  🟡 In Progress |
|      82%      |      10%      |       8%        |

-------------------------------------------------
| Latest Votes                                     |
-------------------------------------------------
| Law Title                         | Status      |
| ---------------------------------- | ----------- |
| Transport Work Regulation         | 🟢 Approved |
| Housing Reform                    | 🔴 Rejected |
| Education Funding                 | 🟡 Ongoing  |

```

📄 Law Profile Page Wireframe

```shell
-------------------------------------------------
| Law Title                                      |
-------------------------------------------------
| Author: Government                             |
| Status: 🟢 Approved                            |
| Approved in 184 days                           |
-------------------------------------------------

Timeline
-------------------------------------------------
Entry → First Vote → Final Vote → Publication

[ Show detailed timeline ▼ ]

-------------------------------------------------
Voting Breakdown
-------------------------------------------------
| Party | Position      |
| PSD   | 🟢 Favor      |
| PCP   | 🔴 Contra     |
| IL    | 🟡 Abstention |
-------------------------------------------------

```

🏛 Party Page Wireframe

```shell
-------------------------------------------------
| Party: PSD                                     |
-------------------------------------------------

| Total Votes Participated | 120 |
| % Favor                  | 62% |
| % Contra                 | 28% |
| % Abstention             | 10% |

-------------------------------------------------
Alignment with Government Initiatives
-------------------------------------------------
| 78% voted in favor of Government proposals |

```

🎨 Tailwind Design Guidelines

Use:

- max-w-6xl mx-auto px-6
- Cards: bg-white shadow rounded-xl p-6
- Status colors:
  - Green: bg-green-100 text-green-700
  - Red: bg-red-100 text-red-700 
  - Yellow: bg-yellow-100 text-yellow-700 
- Typography:
  - Title: text-2xl font-bold
  - Section headers: text-lg font-semibold
  - Metrics: text-4xl font-extrabold


🔥 Optional Small UX Enhancement

Add a “What does this mean?” expandable explanation block on the dashboard using Alpine.

That alone increases citizen accessibility dramatically.
