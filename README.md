# OnCall
jacripe 202506

## Introduction

The "new" OnCall (v2) app is available on the Prometheus monitoring servers.

NOTICE
Please note that logging in is required.

OnCall2 is designed to match the original Xymon hosted OnCall v1 application in style as much as possible, while also improving functionality & smoothing out database design.

- GitHub: 
- User Guide: 
- Database Design: 

![Figure A OnCall 2 Database Design](https:///OnCallv2.png)
Fig. A) OnCall2 Database Design

Current database design for the development version (currently v2.2). Dotted lines indicate portions not yet fully implemented.

> NOTICE:
> Please note that some screenshots will indicate the development version as opposed to the live version. I will attempt to indicate such differences where relevant.
 
## 1. Home Page
### 1.1 Main Page
Upon accessing the homepage for the OnCall2 application, or any page of the application for the first time, you will be prompted to login with your credentials.

![Figure 1.1\) OnCall2 Login Prompt](https:///1_Login.png)
Fig. 1.1) OnCall2 Login Prompt

Once logged in, you will be greeted with a familiar "Current On-Call" type page designed after the original Xymon OnCall app. Schedules displayed are "current" in that they are the newest active schedule with a start date of <= now. Past, future, and inactive schedules are available elsewhere in the application.

> NOTICE:
> Please note that all times expressed within OnCall2 use Universal Coordinated Time (UTC, +/-0000) for consistency across time zones and with server logs.

![Figure 1.2 OnCall2 Current On-Call or Home Page](https:///2_Home-CurrentOnCall.png)
Fig. 1.2) OnCall2 "Current On-Call" / "Home" Page

### 1.2 Header
Please note the header portion of the page includes quick navigation links and a search bar. Each page should include a banner at the top explaining available search terms for the given page and providing working examples.

- Navigation Links:
- -	Current On-Call: "Home" page
- - Schedules: Team specific current schedules list
- - Teams: Team details page
- - Employees: List of all currently available employees

![Figure 1.3 OnCall2 Header](https:///2.1_Home-NavHeaderpng.png)
Fig. 1.3) OnCall2 Header

### 1.3 Main Page Continued
For this guide, I will primarily be using the "[TEAM]" team as that is what I am currently assigned to. You may scroll through the Current On-Call page to find the team relevant to your interest.

Once identified, the following links are available:
- TEAM NAME: Navigates to the relevant team page
- "Schedules": Navigates to the current schedules page for the relevant team
- EMPLOYEE NAME: Navigates to the employee details page for the relevant employee
- - A brief summary of relevant contact information for the employee assigned to the given schedule is also displayed for convenience.

![Figure 1.4 Current On-Call Team Selection](https:///2.3_Home-TeamSelection.png)
Fig. 1.4) Current On-Call Team Selection

## 2. Team Page
Selecting a given team name link on the Current On-Call page will navigate you to the relevant team details page. In this document I will be using [TEAM].

On this page, the Team details are displayed at the top including the following:
- "On-Call Team" (TEAM_SELECT): This selection tool is used to navigate between different teams on team specific pages
- - I have attempted to retain & utilize the current team selection when navigating to "Schedules" or "Teams" via the header navigation links, however this may not yet be fully implemented.
- DB ID: The team's database ID (unsigned integer)
- - Can NOT be changed
- Name: Team name
- - Must be unique
- Start Time (UTC): Default time for new schedule creations
- Days (NDAYS / DAYS_OFFSET): Number of days to skip ahead when appending new schedules
- Create Team: Provides a modulo dialog for creating a new team
- Schedules: Links to team schedules
- Templates: Links to team schedule templates
- Description: Brief description of team responsibilities for display in the [CALLOUT] page
- Employee: Employee details
- Add Employee: This text box & submission button provide the ability to add new team members via employee ID or name
- Active / All: This radial selection permits a display of only active employees within a team or all employees within a team

![Figure 2.1 Team Page for TEAM](https:///4_TeamPage.png)
Fig. 2.1) Team Page for [TEAM]

### 2.1 Create Team
The "Create Team" button will present a modulo dialog for creating a new team.

![Figure 2.2 Create Team Button](https:///4.1_Team-Create-Button.png)
Fig. 2.2) Create Team Button

![Figure 2.3 Create Team Modulo](https:///4.1_Team-Create-Modulo.png)
Fig. 2.3) Create Team Modulo

Once all inputs have been populated and the "Save" submission is pressed, the new team will be created and you will be redirected to its page. Please note that employee assignment and schedule creation for the new team must be performed manually once the team has been created.

![Figure 2.4 OnCall 2.x New Team](https:///4.4_Team-OnCall2-Create-NewTeam.png)
Fig. 2.4) OnCall2.x New Team

If modification of an existing team or creation of a new team will conflict with an existing team's name, you will be redirected to that page with a notice and no modifications will be made.

![Figure 2.5 Create Team Duplicate Name](https:///4.5_Team-OnCall2-Create-Modulo-TeamExists.png)
Fig. 2.5) Create Team Duplicate Name

![Figure 2.6 Team Exists](https:///4.6_Team-OnCall2-Create-TeamExists.png)
Fig. 2.6) OnCall2.x Dev Team Exists

### 2.2 Team Employees
The "Employees" portion of the page provides a summary of employee information with the following options available.

- Active / All: Radial toggle for displaying only Active employees or All employees on the team
- Name: Employees preferred name
- Navigates to the relevant employee details page
- UserID: Employee ID
- Audinet: "Internal" / "Desk" Phone Number
- Mobile: Employee Cell
- - Preferably Corporate Cell
- Text Message: Email to SMS address for notifications
- "Remove Employee" (icon/button): Clicking the "silhouette-minus" button will remove the given employee from the current team

![Figure 2.7 Team Employee Details](https:///4.7_Team-Employees.png)
Fig. 2.7) Team Employees Details

## 3. Schedules
By navigating to the "Schedules" (Current) page via the "Schedules" link on the Current On-Call page, or the "Schedules: Current" link on the team page, you will be taken to the current schedules for this team. Current schedules are calculated as the one immediately preceding now, plus all active future schedules. Please note that all inputs are available to modify "current" schedules and changes will be immediately applied.

Schedule options & details are as follows:
- Add Schedule / Append Schedule: This button will provide a modulo dialog for appending a new schedule to the end of the list
- Monthly: Navigates to the Monthly Schedule page for the current team
- Team: Navigates to the Team page for the current team
- Start Date: Date & time (UTC) of when the schedule begins
- On-call Person: Employee assigned to the relevant schedule
- Call Order: Order of priority for the assigned employee using an unsigned integer
- - Schedules may have multiple employees sharing the same "Call Order"
- - Schedules may only include an employee 0 or 1 times.
- "Add Employee" (icon/button): The "silhouette-plus" icon will provide a modulo dialog for adding additional employees to the relevant schedule
- “Remove Employee” (icon/button): The “silhouette-minus” icon will remove the relevant employee from the given schedule
- - This option is only available when there are more than 1 employees on a single schedule
- "Insert Schedule" (icon/button): The "calendar-plus" icon will create a new schedule with a "Start Date" of the relevant schedule + 1 day using the first (lowest) "Call Order" employee on the current schedule.
- - "Disable Schedule" (icon/button): The "calendar-minus" icon will disable the relevant schedule

![Figure 3.1 Current Schedules](https:///5_Schedules-Current.png)
Fig. 3.1) Current Schedules

## 3.1 Schedule Backups
Clicking the "Add backup" icon/button on the Schedules page provides the following modulo dialog for adding multiple employees to a single schedule. "(Schedule #)" refers to the given schedule's database ID, Employee will default to the first employee for the team, & "Call Order" will default to 1.

![Figure 3.2 Schedule Add Backup](https:///6.1_Schedules-AddEmployee.png)
Fig. 3.2) Schedule Add Backup

> NOTICE:
> Multiple employees may share a Call Order, but each employee can only be assigned to a single schedule once. If an existing employee is attempted to be added to a schedule they are already assigned to, then their call order will only be updated instead.


![Figure 3.3 Add Backup / Add Employee Modulo Dialog, Redundant](https:///6.2_Schedules-AddEmployee-Modulo-Redundant.png)

Fig. 3.3) Add Backup / Add Employee Modulo Dialog, Redundant

![Figure 3.4 Redundant Backup / Employee Call Order Update](https:///6.2_Schedules-AddEmployee-Redundant.png)
Fig. 3.4) Redundant Backup / Employee Call Order Update

Adding a different employee will assign them to the same schedule, using the "Call Order" provided in the modulo dialog.

![Figure 3.5 Add Backup / Add Employee, Non-redundant](https:///6_Schedules-AddEmployee.png)
Fig. 3.5) Add Backup / Add Employee, Non-redundant

Call order has no relevance other than display order.

![Figure 3.6 Schedule With Backup](https:///7.1_Schedule-Backup.png)
Fig. 3.6) Schedule With Backup

To remove an employee / backup from a schedule, simply click the "Remove Backup" icon / button. Alternatively, you may set the given employees "Call Order" to 0 in order to achieve the same result. Although the database does not enforce this association, attempting to remove the last employee from a schedule will instead revert the employees "Call Order" back to 1.

![Figure 3.7 Remove Backup / Remove Employee](https:///7.2_Schedule-RemoveBackup.png)
Fig. 3.7) Remove Backup / Remove Employee

![Figure 3.8 Backup / Employee Removed](https:///7.3_Schedule-RemoveBackup.png)
Fig. 3.8) Backup / Employee Removed

Having multiple employees / backups on a "Current" schedule will also effect "Current On-call" page display. Each employee will be shown in order with only the "Contact Instructions" of the last employee displayed.

![Figure 3.9 Current Schedule With Backup](https:///7.4_Schedule-CurrentBackup.png)
Fig. 3.9) Current Schedule With Backup

![Figure 3.10 Current On-Call Schedule With Backup](https:///7.5_Schedule-HomeCurrentBackup.png)
Fig. 3.10) Current On-Call Schedule With Backup

### 3.2 Add Schedule / Append Schedule
Using the "Add Schedule" / "Append Schedule" button will provide a modulo dialog for adding a new schedule to the end of the list.

![Figure 3.11 Add Schedule / Append Schedule Button](https:///8.1_Schedule-AddAppend.png)
Fig. 3.11) Add Schedule / Append Schedule Button

The "Add Schedule" / "Append Schedule" modulo dialog populates with the following defaults:
- Start Date: Last active schedule + team NDAYS / DAYS_OFFSET
- Employee: First employee in the team
- Call Order: 1

![Figure 3.12 Add Schedule / Append Schedule Modulo](https:///8.2_Schedule-AddAppend-Modulo.png)

Fig. 3.12) Add Schedule / Append Schedule Modulo

![Figure 3.13 New Schedule Added / Appended](https:///8.2_Schedule-AddAppend-NewSchedule.png)
Fig. 3.13) New Schedule Added / Appended

### 3.3 Insert Schedule
Using the "Insert Schedule" icon / button will instantly create a new schedule using the following defaults:
- Start Date: Relevant schedule Start Date + 1 day
- Employee: First Employee on relevant schedule by Call Order
- Call Order: 1

![Figure 3.14 Insert Schedule Icon](https:///8.3_Schedule-Insert.png)
Fig. 3.14) Insert Schedule Icon / Button

![Figure 3.15 New Schedule Inserted](https:///8.4_Schedule-Insert-NewSchedule.png)
Fig. 3.15) New Schedule Inserted

### 3.4 Disable Schedule
Using the "Disable Schedule" icon / button will update the schedule's "active" boolean to FALSE. This will prevent the schedule from being displayed on the "Current On-Call" & "Current Schedules" pages.

![Figure 3.16 Disable Schedule Button / Icon](https:///9.1_Schedule-Disable.png)
Fig. 3.16) Disable Schedule Button / Icon

![Figure 3.17 Disabled Schedule Excluded](https:///9.2_Schedule-Disabled.png)
Fig. 3.17) Disabled Schedule Excluded

Disabled & old schedules will still display on both the "Monthly Schedules" & "All Schedules" pages.

![Figure 3.18 Monthly Schedules With Disabled Schedule](https:///9.3_Schedule-Monthly-Disabled.png)
Fig. 3.18) Monthly Schedules With Disabled Schedule
  
Disabled schedules may be re-activated on these pages by using the "Enable Schedule" button / icon.

![Figure 3.19 Enable Schedule Button / Icon](https:///9.4_Schedule-Monthly-Enable.png)
Fig. 3.19) Enable Schedule Button / Icon

![Figure 3.20 Schedule Re-enabled](https:///9.5_Schedule-Monthly-Enabled.png)
Fig. 3.20) Schedule Re-enabled

Alternatively, attempting to create a new schedule for the same team and start date as a disabled schedule, will simply re-activate the disabled schedule and update it with the information provided.

![Figure 3.21 Add Schedule / Append Schedule Duplicate Modulo Dialog](https:///9.8_Schedule-AddAppend-Reactivate-Modulo.png)
Fig. 3.21) Add Schedule / Append Schedule Duplicate Modulo Dialog

![Figure 3.22 Add Schedule / Append Schedule Duplicate Re-activated](https:///9.9_Schedule-AddAppend-Reactivated.png)
Fig. 3.22) Add Schedule / Append Schedule Duplicate Re-activated

### 3.5 All Schedules / Monthly Schedules
Using the "Monthly" schedules link from either the Team or Schedules pages will bring you to the "All Schedules" page with the relevant monthly filter applied.

The available information and options are as follows:
- "<" (Previous Month): Navigates to the previous month's schedules
- Today: Navigates to the current month's schedules
- ">" (Next Month): Navigates to the next month's schedules
- All: Navigates to a list of all schedules for the current team
- Team: Navigates to the current team's details page
- Schedule List
- - ID: Relevant schedule's database ID (unsigned integer)
- - - Navigates to schedule's details page
- - Start Date: Schedule's start date
- - On-Call Person: Schedule's assigned employees / backups
- - Call Order: Call order of relevant employee / backup
- - "Enable Schedule" (icon/button): Pressing the "calendar-plus" icon/button will re-activate the relevant disabled schedule

![Figure 3.23 Monthly Schedules Page](https:///9.10_Schedule-Monthly.png)
Fig. 3.23) Monthly Schedules Page

Old schedules cannot be re-activated or altered as they are already outdated and are retained solely for historical reference.

![Figure 3.24 Monthly Schedule With Old Schedules](https:///9.6_Schedule-Monthly-Old.png)
Fig. 3.24) Monthly Schedules With Old Schedules

### 3.6 All Schedules
The "All Schedules" page provides a list of all existing schedules, whether outdated, disabled, or otherwise, for the relevant team. The available options and information are a near exact duplicate to the "Monthly Schedules" page.

![Figure 3.25 All Schedules Page](https:///9.11_Schedule-All.png)
Fig. 3.25) All Schedules Page

### 3.7 Schedule Details
The "Schedule Details" page provides all available information for a single schedule. Relevant fields can be updated using the included inputs and all changes are immediately applied.

Options and information are as follows:
- Schedule ID: Database ID (unsigned integer) for the schedule
- - May be used in searches
- - Can NOT be changed
- Active / Inactive: Radio option for enabling / disabling the schedule
- Start Date: Start date for the schedule
- - Please note that setting the start date older than the "Current" schedule for the team will cause the schedule to become "Old" (outdated) and no longer permit any changes to be applied.
- On-Call Team (ID): Name, database ID, & link to the relevant team page
- - Can NOT be changed
- Schedules Current / Month: Links to the relevant team's "Current Schedules" & "Monthly Schedules" pages
- Employees: List of employees / backups on the schedule
- - Call Order: Relevant employee's call order
- - Employee: Employee selection for the schedule

![Figure 3.26 Schedule Details Page for Active Schedule](https:///9.12_Schedule-Details.png)
Fig. 3.26) Schedule Details Page for Active Schedule

Please note that "Old" / "Outdated" schedules cannot be modified and are retained only for historical reference.

![Figure 3.27 Schedule Details for Old Schedule](https:///9.7_Schedule-Details-Old.png)
Fig. 3.27) Schedule Details for Old Schedule

## 4. Employees

Navigating to the "Employees" page will bring you to a list of all current active employees.

Options and information available are as follows:
- Create Employee: Navigates to the "New Employee" page
- Name: Employees preferred name
- - Based on "Nickname" + "Last Name"
- - Navigates to employee details page
- UserID: Employee ID
- Audinet: "Internal" / "Desk" phone number
- Mobile: Employees cell phone number
- - Preferably corporate cell number
- Text Message: Email to SMS notification email address for notification purposes
- - * DEPRECATED * Updated to twilio

![Figure 4.1 Employees Page with All Employees](https:///15.1_Employees-All.png)
Fig. 4.1) Employees Page with All Employees

### 4.1 Employee Details
Selecting an employee name will present you with the employee details page.

Options and information are as follows:
- Database ID: Employee database ID
- - Unsigned Integer
- - Can be used in searches
- - Can NOT be changed
- User ID: Employee ID
- - Must be unique
- - Can be used in searches
- - Can NOT be changed
- Employee Type: Nature of employment
- - i.e. Employee (Direct Hire) vs. Contractor
- - Almost all Employees will be of type "Employee"
- First Name
- Nickname
- Last Name
- - First & Last Names can NOT be changed
- - Can be used in searches
- - - i.e. "Last, First", "Nickname Last", "First Last"
- Audinet: "Internal" desk phone number
- Direct Dial: "External" desk phone number
- Primary Cell: Corporate or personal primary cell number
- - Required for SMS notifications
- Other Cell: Personal or secondary other cell number
- Home Phone
- Corp Email
- Text Message: Email to SMS email address for notifications
- Other Email: Personal or other email address
- Contact Instructions: Usually backup contact information
- Active / Inactive: Enable or disable the employee
- Teams: Checkbox list of teams for employee assignment
- - Active teams only
- Save: Commits changes to employee

> NOTICE:
> Changes are NOT applied until the "Save" submission is pressed

> NOTICE:
> Employee Name ("[First] [Last]") MUST be unique

> NOTICE:
> Employee displayed name is formed by "[Nickname OR First] [Last]"

![Figure 4.2 Employee Details Top](https:///15.2_Employees-Details.png)
Fig. 4.2) Employee Details Top
 
![Figure 4.3 Employee Details Bottom](https:///15.2b_Employees-Details-Bottom.png)
Fig. 4.3) Employee Details Bottom

### 4.2 Create Employee
Using the "Create Employee" button on the "Employees" page will bring you to the "New Employee" page, where you may populate information for the new employee. Once the required & optional fields have been filled appropriate, you may use the "Save" submission at the bottom to commit your changes.

All information presented on the "Employee Details", with the exception of the "Database ID," is available for manual input. Please be careful with the "User ID" specifically as this value cannot be modified through the application post-submission.

![Figure 4.4 Create Employee Button](https:///15.3_Employees-Create-Button.png)
Fig. 4.4) Create Employee Button

![Figure 4.5 Create Employee Page](https:///15.4_Employees-Create-Page.png)
Fig. 4.5) Create Employee Page

Upon successful submission of the "New Employee" information, you will be redirected back to the "Employee Details" page for the newly created employee.

![Figure 4.6 Employee Created](https:///15.5_Employees-Created.png)
Fig. 4.6) Employee Created

If any unique information from an employee creation or existing employee modification conflicts with any existing employee (i.e. User ID or "Last Name" + "First Name" + "Nickname"), you will be redirected to the existing employee's details page with no changes applied and a message provided.

![Figure 4.7 Duplicate Employee Exists](https:///15.6_Employees-Create-Exists.png)
Fig. 4.7) Duplicate Employee Exists

## 5. Templates
> NOTICE:
> This new addition to OnCall v2.1 is a continuing work in progress

Navigating to the “Templates” page will bring you to a list of the relevant Team’s active templates.

Available options & information are as follows:
- Append Template: Creates a new template at the end of the list
- - “Days Offset” defaults to the Team’s “NDays” value after the last template
- - “Start_Time” defaults to the time of the last template
- Apply Templates: Uses the existing templates to create a new set of schedules
- - New schedules start “Days Offset” after the last currently active schedule for the team
- - Start time is appended to the “Start Date” of the new schedules
- Schedules: Links to the team’s current schedules
- Team: Links to the team details page
- Templates List
- - Days Offset: Number of days to append to the last schedule
- - - Uses an integer value to permit less than or greater than one week difference
- - Start Time: Time applied to the new schedules upon creation
- - On-Call Person / Call Order: List of employees to assign to new schedules with their associated Call Order
- Insert Template: Creates a new template after the selected template
- - Days Offset: Current template’s “Days Offset” +1
- - Start Time: Duplicates current template’s “Start Time”
- - On-Call Person / Call Order: Duplicates the current template’s first employee
- Add / Remove Employee: Functions the same as on the Schedules page

![Figure 5.1 Team Schedule Templates](https:///28_Templates.png)
Fig. 5.1) Team Schedule Templates

# Appendix A: Project Information
## Name
OnCall v2.1.5

## Description
OnCall allows employees to create & modify teams, employees, schedules, or templates via the Web UI.

It is a simple PHP replacement for the original Xymon hosted version that emphasis direct user interface as opposed to back end database edits. All actions on the production version are logged within the database itself.

## Installation
Download & unpackage the appropriate release package to the intended server running suitable HTTP/s & MySQL type servers as well as an appropriate PHP handler with PDO installed.

Duplicate or rename the OnCall2.conf.php.example file to OnCall2.conf.php with appropriate ownership / permissions for the web server & PHP handler, then update the `DB_HOST`, `DB_NAME`, `DB_USER`, & `DB_PASS` passwords with the relevant information.

EXAMPLE)
```
[user@workstation ~]$ scp [package].tar.gz [user]@server:~

[root@server ~]# cd /var/www/html
[root@server /var/www/html]# tar -xvzf /home/[user]/[package].tar.gz
...
[root@server /var/www/html]# mv -v oncall-[release] oncall
[root@server /var/www/html]# chown -Rv apache:apache ~+/oncall
[root@server /var/www/html]# mv -v ~+/oncall/OnCall2.conf.php.example ~+/oncall/OnCall2.conf.php
[root@server /var/www/html]# vim /var/www/html/oncall/OnCall2.conf.php
```

## Support
Please contact Joshua A. Cripe for assistance.
- Email:
- Chat:
- Work phone:
- Mobile:
- Location:
- Department:

## Contributing
Contributions are open to any parties interested in assisting with the maintenance of this project. Please create an [issue](https:///oncall/-/issues) or contact Joshua A. Cripe with requests.

## Authors and acknowledgment
Created By:
- Joshua A. Cripe

Based On Xymon/oncall By:
- [Jeff]

## License
(K) 2025 Kopyleft - Some rights reversed
