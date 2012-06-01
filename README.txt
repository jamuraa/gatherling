Gatherling

An online MTG website for running tournaments.

Setup:
1. Copy config.php.example to config.php and fill in the
   variables needed.
2. Insert schema.sql into the database.
3. Sign up a player by visiting register.php.
4. Set the player as an admin by running SQL:
   UPDATE players SET super = 1 WHERE name = '<username>';

There are about 20 more steps.  We realy need to fix this.
