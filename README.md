# UnicodeCleaner
UnicodeCleaner is a webtool for cleaning misinterpreted characters.

## Introduction to the problem 

### The symptoms: weird characters. 
A table with Collation utf8_bin has misinterpreted characters. 

See figure below, it portrays the structe of a table in phpMyAdmin.

 ![The structure of a table](https://github.com/jmediatechnology/UnicodeCleaner/blob/master/img/01_structure_of_table.png "This table uses utf8_bin")

 

See figure below, it portrays the contents of a table in phpMyAdmin. 

 ![The content of a table](https://github.com/jmediatechnology/UnicodeCleaner/blob/master/img/02_misinterpreted_content.png "The table contains data with misinterpreted characters")
 

The textual representation of the table: 

Char test Î©\
plantaardige oliÃ«n\
product price: â‚¬ 10,00.\
pokÃ©mon\
MAÃS (contains control chars)\
pinda?s\
MELKPROTEÃNE (contains control chars)\
weiÃŸ (contains control chars)\
BÃŠTAŸ (contains control chars)\
Ã  tout Ã  l''heureŸ (see you soon) (contains control chars)\
Ă  bientĂ´t (see you soon) (contains control chars)\
Il est Ã  Paris (he is in Paris) (contains control chars)

 
### Why are some characters displayed in such weird way? 
See figure below. 

 ![Why are some characters displayed in such weird way](https://github.com/jmediatechnology/UnicodeCleaner/blob/master/img/03_Why_are_some_characters_displayed_in_such_weird_way.png "How the misinterpretation could happen")


UTF-8 represents the Ω symbol on a binary level like this: 11001110 : 10101001.\
In hex those 2 bytes are represented like this: 0xCE 0xA9. \
The decimal values are: 206 : 169.\
Link: https://www.fileformat.info/info/unicode/char/03a9/index.htm

In ISO-8859-1 the byte sequence of 11001110 : 10101001 represents these characters: Î©. \
Bin: 11001110 = hex: 0xCE, dec: 206 and represented as Î.\
Bin: 10101001 = hex: 0xA9, dec: 169 and represented as ©.\
Link: http://www.fileformat.info/info/charset/windows-1252/list.htm

Let’s put in a table for comparison: 

| Char   | ISO-8859-1          | UTF-8                          |
| ------ |:-------------------:| ------------------------------:|
| Ω      | N/A                 | Hex: 0xCE 0xA9, dec: 206 : 169 |
| Î      | Hex: 0xCE, dec: 206 | Hex: 0xC3 0xAE, dec: 195 : 174 |
| ©      | Hex: 0xA9, dec: 169 | Hex: 0xC2 0xA9, dec: 194 : 169 |


So, displaying Ω as Î© is just a matter of choosing the wrong encoding for the representation of characters. \
UTF-8 represents 11001110 : 10101001 as Ω. \
Windows-1252 represents 11001110 : 10101001 as Î©.

Things get complicated when the misinterpreted characters are resaved. \
The byte values of misinterpreted characters will change to their misinterpreted byte values.  


 
## The problem: a misinterpreted byte sequence
When the collation of a table is utf8_bin, but Ω is displayed as Î©, it means that there was a misinterpretation during the insertion of data and that the byte sequence is stored with a misinterpreted byte sequence.

The characters Î and © exists in UTF-8.\
In UTF-8 the character Î is represented as: 11000011 : 10101110, hex: 0xC3 0xAE, dec: 195 : 174.\
In UTF-8 the character © is represented as: 11000010 : 10101001, hex: 0xC2 0xA9, dec: 194 : 169.\
Instead of inserting 0xCE 0xA9 (Ω), 0xC3 0xAE 0xC2 0xA9 (Î©) was inserted.

The byte sequence in hex: 0xC3 0xAE 0xC2 0xA9 needs to be: 0xCE 0xA9.\
Thus Î© needs to be Ω. 

On the binary level, the byte sequence 11000011 : 10101110 : 11000010 : 10101001 must be converted to: 11001110 : 10101001.

 
## How to solve the misinterpretations? 
In order to clean the misinterpretations the misinterpreted byte sequence must convert to intended byte sequence. This is possible with this algorithm. 

As an example, Î© needs to be Ω.

 ![How to clean](https://github.com/jmediatechnology/UnicodeCleaner/blob/master/img/04_how_to_solve_misinterpretations.png "Cleaning is possible by reinterpreting the misinterpreted characters")
 

First, the binary values of the misinterpreted characters from a specific encoding must be found.\
In ISO-8859-1, the binary value in hex of Î is 0xCE, the binary value in hex of © is 0xA9.\
Those two byte values combined (0xCE 0xA9) represent in UTF-8 the Ω symbol.




### Will a change of collation in phpMyAdmin restore the misinterpretations?
Short answer: YES and NO, it depends.\
Long answer: 

When you try to change the collation a warning message will pop-up. 

This operation will attempt to convert your data to the new collation. In rare cases, especially where a character doesn't exist in the new collation, this process could cause the data to appear incorrectly under the new collation; in this case we suggest you revert to the original collation and refer to the tips at Garbled Data.

Are you sure you wish to change the collation and convert the data?

A change in collation on the table works in this situation
Let’s say the collation is latin1_bin, and the following text is in a cell: 

Char test Î©

In case of altering the collation to utf8_bin the result will be: 

Char test Ω

The downside of this method is that it changes the collation for the whole table. 
When a table uses more than one collation data might become Garbled. 
UnicodeCleaner doesn’t change the database collation, it does something different. 

A change in collation on the table DOES NOT work in this situation
Let’s say the collation is utf8_bin, and the following text is in a cell: 

Char test Î©

In case of altering the collation to latin1_bin the result will be: 

Char test ÃŽÂ©

The text is now double garbled because ISO-8859-1 doesn’t have the Ω symbol. 
UnicodeCleaner prevents double garbling.  

### So what does UnicodeCleaner do to clean the misinterpretations? 
UnicodeCleaner fetches data from the database, then uses Iconv to clean misinterpretation, and then it saves data back to the database. 

Read below why iconv was chosen. 


In php, cleaning misinterpretations is possible with these functions: 

•	Utf8_decode
o	Restores only from UTF-8 to ISO-8859-1
•	Mb_convert_encoding
o	Works with different in- and out encoding
o	Invalid byte sequences are turned to ? 
•	Iconv
o	Works with different in- and out encoding
o	Invalid byte sequences triggers a E_NOTICE (php is capable of handling E_NOTICE error with the error handling function registered by set_error_handler() )

The software UnicodeCleaner uses Iconv as its core because Iconv triggers an E_NOTICE. 
This E_NOTICE will be converted to an Exception, and this Exception must prevent garbling of data. 

IMPORTANT
In order to clean the misinterpreted characters, the name of the original encoding must be known.








 
## Test phase

### Test #1 From UTF-8 to Windows-1252 
The following settings are used: 

Database connection encoding: UTF-8
From encoding: UTF-8
To encoding: WINDOWS-1252

Test results: 
Converting from UTF-8to WINDOWS-1252
Amount of converted cells: 4 cells. 
Amount of ignored cells: 8 cells.

Screenshot with UI: 

  ![Test_1_UI](https://github.com/jmediatechnology/UnicodeCleaner/blob/master/img/05_test_1_utf8_to_windows1252.png "The user interface")


The result in the database: 

   ![Test_1_DB](https://github.com/jmediatechnology/UnicodeCleaner/blob/master/img/06_test_1_utf8_to_windows1252.png "The content of the table")

The green arrow points to data that was successfully converted. However, not all characters could be cleaned, but a double misinterpretation didn’t happen which is a good thing. 

The remaining misinterpreted characters must be cleaned with a different encoding.

Textual representation: 

Char test Ω\
plantaardige oliën\
product price: € 10,00.\
pokémon\
MAÃS (contains control chars)\
pinda?s\
MELKPROTEÃNE (contains control chars)\
weiÃ (contains control chars)\
BÃŠTA (contains control chars)\
Ã  tout Ã  l'heure (see you soon) (contains control chars)\
Ă  bientĂ´t (see you soon) (contains control chars)\
Il est Ã  Paris (he is in Paris) (contains control chars)






### Test #2 From UTF-8 to ISO-8859-1 
The following settings are used: 

Database connection encoding: UTF-8
From encoding: UTF-8
To encoding: ISO-8859-1

The result was: 
Converting from UTF-8to ISO-8859-1
Amount of converted cells: 3 cells. 
Amount of ignored cells: 9 cells.

Screenshot with UI:

   ![Test_2_UI](https://github.com/jmediatechnology/UnicodeCleaner/blob/master/img/07_test_2_utf8_to_iso88591.png "The user interface")

 
The result in the database: 

   ![Test_2_DB](https://github.com/jmediatechnology/UnicodeCleaner/blob/master/img/08_test_2_utf8_to_iso88591.png "The content of the table") 

The green arrows marks the cleaned data. 
Unfortunately, the French characters couldn’t be restored. 


 
### Test #3 French characters with Iconv
The text: 
Ã  tout Ã  l'heure (see you soon)
Should be: 
à tout à l'heure (see you soon)

Unfortunately, Iconv was unable to clean it. 

When the misinterpreted character is Ã then tracing back the original character with the conversion method describes in “How to solve” is impossible. The reason for it is because: à, Ý, Ð, Ï, Í, Á are all misinterpreted as Ã. 

 ![Test_3_French_chars_with_iconv](https://github.com/jmediatechnology/UnicodeCleaner/blob/master/img/09_test_3_french_characters_with_iconv.png "Iconv is not able to clean all misinterpretations") 
    


So misinterpreted French character should be cleaned with a manual translation table. 
This is possible by defining patterns and replacements. 
The pattern "Ã  " could translate to "à ". 

The pattern and replacement can be defined at Clean by Translation Table. 


 
### Test #4 French characters with the Translation Table
Cleaning misinterpreted characters with a manual translation table works by defining a pattern that maps to a specific replacement. For example: the pattern "Ã  " could translate to "à ". 

The pattern and replacement can be defined at Clean by Translation Table by clicking the Translation table button. The Translation table for this example looks like this: 

' Ã '= à \
'Ã '=à \
'Ă '=à \
'Ă´'=ô\
'ÃŠ'=Ê\
'?s'='s

After defining the Translation table the table could be reinterpreted. The reinterpretation happens by the configured Translation table. Screenshot of UI after pressing the Reinterpret button: 

  ![Test_4_French_chars_with_translation_table](https://github.com/jmediatechnology/UnicodeCleaner/blob/master/img/10_test_4_french_characters_with_translation_table.png "Reinterpret according to a Translation Table") 
 
Test results in database: 

  ![Test_4_French_chars_with_translation_table_db](https://github.com/jmediatechnology/UnicodeCleaner/blob/master/img/11_test_4_french_characters_with_translation_table.png "Contents of the table after reinterpreting by Translation Table") 
  

The green arrows marks the cleaned data.

When the Reinterpret button is clicked again, then there is nothing to reinterpret. 

  ![Test_4_French_chars_with_translation_table_prevent_reinterpret_twice](https://github.com/jmediatechnology/UnicodeCleaner/blob/master/img/12_test_4_french_characters_with_translation_table.png "Prevent reinterpret twice") 
  


 
 
### MySQL queries for testing 
Test queries.


CREATE TABLE `target` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `text` varchar(255) COLLATE utf8_bin NOT NULL,
 PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin; 


INSERT INTO `target` (`text`) VALUES 
('Char test Î©'), 
('plantaardige oliÃ«n'),
 ('product price: â‚¬ 10,00.'),
 ('pokÃ©mon'),
 ('MAÃS (contains control chars)'),
 ('pinda?s'),
 ('MELKPROTEÃNE (contains control chars)'),    
	('weiÃ (contains control chars)'),
	('BÃŠTA (contains control chars)'),
	('Ã  tout Ã  l''heure (see you soon) (contains control chars)'),
	('Ă  bientĂ´t (see you soon) (contains control chars)'),
	('il est Ã  Paris (he is in Paris) (contains control chars)');



