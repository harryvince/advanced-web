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
TODO:
- [ ]  ~~An appropriate database backend that demonstrates you have considered the assessment scenario and all requirements you have implemented~~
- [ ] ~~Appropriate data models implemented in your application~~
- [ ] ~~Read records from a database and return valid XML or JSON output~~
- [ ] ~~Create records in a database and return valid XML or JSON output~~
- [ ] ~~Update records in a database and return valid XML or JSON output~~
- [ ] ~~Delete records from a database and return valid XML or JSON output~~
- [x] Provide valid returns in both XML and JSON
- [x] Implement  appropriate  security  so  that  a  user  is  required  to  authenticate  to carry out tasks (such as Create, Update and Delete requests)

Tasks that are striken through cannot yet be completed due to lack of assignment. 

All core work has been completed just waiting on a scenario to accurately model the database and application around that

# Creator
Harry Vince