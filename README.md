# synegastorage

The application has a basic auth, an email and a password is required for whatever api call you wish to make, even when downloading a file.
Please see the /database/seeds/UsersTableSeeder.php to add new users.
Currently there a test user: andrei.hurjui@gmail.com    secretpass

##API Routes

#1. GET /api/files

    a. Description

        Get a list of all files available, that were not deleted

    b. Params - none

    c. Response:

        - 200 OK - a list with the files from the database

        - 400 - error with a message about the error


#2. GET /api/files/{id}

    a. Description

        Get the information about a single file

    b. Params:

        - id : int, the id of the file that you wish to get from information for

    c. Response:

        - 200 OK - an object containing the infromation about the file

        - 400 - an error with a message


#3. GET /files/download/{id}/{name}

    a. Description

        Download a file. Use curl or open the url in a browser.

    b. Params:

        - id : int, the id of the file that you wish to be downloaded

        - name: string, the name that the file will have when it will be downloaded. By default, the url download will contain the original file name, but if

                you wish to change it just use something else.

    c. Response:

        - 200 OK - the download is ok

        - 400 - an error as occurred and message is returned


#4. POST /files/

    a. Description

        Uploads a file to the server and saves information about the file into the database

    b. Params:

        - file - in the body of the request, the file to be uploaded

    c. Response

        - 200 OK - the information that is saved into the database is returned to the client

        - 400 - an error has occurred and a message is returned


#5. PUT /files/{id} - for Ngnx

   POST /files/{id}, into the body of the request add _method = PUT, this is for Apache

    a. Description

        Updates a file into the database. When the call is made, the application will upload the new file, will save new data about the file into the database and also

        will move the old file into a directory called recycle.

    b. Params:

        - file - the new file to be uploaded

        - id - the id of the file to be updated

    c. Response

        - 200 OK - an object with the information about the file is returned

        - 400 - an error has occurred and a message error is returned


#6. DELETE /files/{id} - for Ngnx

   POST /files/{id}, into the body of the request add _method = DELETE, this is for Apache

    a. Description

        Deletes a file. The application will mark the record into the database as deleted and the file will be moved to a directory called recycle.

        This method does not physically removes a file from the server.

    b. Params:

        - id - int the id of the file to be "deleted"

    c. Response:

        - 200 OK - A message that the file was marked as deleted

        - 400 - an error has occurred and a message is returned


#7. DELETE /files- for Ngnx

   POST /files, into the body of the request add _method = DELETE, this is for Apache

    a. Description

        Physically removes all the files from the recycle directory.

    b. Params - none

    c. Response:

        - 200 OK - with a message that the directory was cleaned

        - 400 - an error has occurred and a message is returned
        