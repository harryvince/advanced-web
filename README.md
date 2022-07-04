# advanced-web
This is a repository dedicated to the development of the practical element of CMP332 Advanced Web

# Dependencies
- Docker
- XAMMP (Development)

# Development
For development XAMMP is required for live changes.
1. Configure XAMPP's Htdocs to point to the src directory of the repo
2. Start the XAMPP Server, if you can see OK on http://localhost the site is up
3. Finally execute the following in the core repo directory
```
docker-compose up
```
You should now be able to make calls to the API

# Production / Deployment
Launch production simply with the following command
```
docker-compose -f docker-compose-full.yaml up
```

# Notes
<b>Task:</b> 

Your company has been approached to develop and Application that allows their users to manage their favourite books.

<b>TODO:</b>
- [ ]  An appropriate database backend that demonstrates you have considered the assessment scenario and all requirements you have implemented
- [x] Appropriate data models implemented in your application
- [x] Read records from a database and return valid XML or JSON output
- [x] Create records in a database and return valid XML or JSON output
- [x] Update records in a database and return valid XML or JSON output
- [x] Delete records from a database and return valid XML or JSON output
- [x] Provide valid returns in both XML and JSON
- [x] Implement  appropriate  security  so  that  a  user  is  required  to  authenticate  to carry out tasks (such as Create, Update and Delete requests)

<b>Personal TODO:</b>
- [ ] Only allow users to see books from their user ID 

Assignment has now been handed out therefore the requirements unstruck and new scenario

# Creator
Harry Vince