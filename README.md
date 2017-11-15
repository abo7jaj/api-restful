# Introduction
This API RESTful is written in PHP and based on a MySQLi database. It can handle any type of content, it digest it and adapt to what ever you provide.

# Authentication 
Auth is made, or : 
- by api_key
- by GET user & password
- by Apache authentication

# Main features
This API have an EDIT_MODE :during a POST or PUT, the table adapts to the content provided :
- if content type is new, it create a table
- if content have new columns, the table will be adjusted

There are a search features : global, by columns, ordered etc.

This RESTful API is secured by a ban system for those who do not know how to implement it. 20 errors / 24 hours is the limit before be banned.

Method OPTIONS provide fields descriptions !

# Releases
First version accept POST, GET, PUT, DELETE methods

Second version accept image directory management and image upload in a secure way

Third version give catalog at root call (only in edit mode for safety)
- I've added a cache engine callable in begining of function
  -  $api->metier->cache(3600*24); // return cache if available, 1day
- I've added a session/context système based on token
  - $api->session
- Method OPTIONS provide fields descriptions 

# Coding
If you want to bypass a functionality, just implement a new class according to the interface provided.

Usages are collected in database for further statistics, system collect location and ISP informations (if activated)

Many logs are generated, SQL and API calls

# Demo
There's a sample directory to learn how to use it : /samples

# TO DO LIST
An admin panel is on the way, stay tuned :
![Admin Panel](https://img4.hostingpics.net/pics/540494Pressepapier10.jpg)

