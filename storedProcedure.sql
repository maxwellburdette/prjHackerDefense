/* storedProcedure.sql - Stored procedures used by registration.php in the sunrun database. 
 * Student Name
 * Written Current Date
 * Revised 
 * Original SQL statement: "SELECT id_runner, CONCAT(fName,' ',lName) AS 'name' 
FROM runner ORDER BY lName";
 */
# Change the default delimiter so you can use ; in the SQL statement 
DELIMITER // 
CREATE PROCEDURE getRunnerList()
BEGIN
  SELECT * FROM runner;
END//
# Set the SProcedure delimiter back to its default 
DELIMITER ;