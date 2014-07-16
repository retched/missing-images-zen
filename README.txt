****************************************************************************
    Missing Images Checker for ZenCart
    Copyright (C) 2014  Paul Williams/IWL Entertainment

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************
Features
========
Scans your ZenCart database and images folder for any missing images and reports
on them.

Version Date
==============
v1.0 2014-07-16 02:37
  * Initial release

Author
======
Paul Williams (retched@iwle.com)

Description
===========
This script will run through your ZenCart database, provided by you in the
connection information of the script, and then retrieve all images from products 
table of ZenCart. Then, the script will run through each of those images making 
sure that the image exists and is saved in the correct format. (For example, a 
.gif is actually a GIF.) This is useful if you use a batch product uploader like 
easyPopulate and you don't know which of your images are missing.

Known Issues
============
    * People on shared servers or those without the ability to turn Safe Mode off
      will experience more time outs on larger databases. This is because I 
      cannot force the script to essentially reset the execution_timer. This will
      be worked on in a later version. You can try to edit the script on line 121 
      or line 145 and change the SQL query a little bit. If you do, you may end 
      up having to run this script a couple of times.

Support thread
==============
http://www.zen-cart.com/downloads.php?do=file&id=1318

Affected files
==============
None (images are NOT modified)

Affects DB
==========
None (database is NOT modified)

DISCLAIMER
==========
Installation of this contribution is done at your own risk.
While there are no changes made to your database or files, it is still suggested 
to backup your ZenCart database and any and all applicable files before 
proceeding.

Install
=======
  0. Backup your database.
  1. Unzip and edit /missing_images.php under the area "Configuration Variables". 
     Be careful of ANY quotation marks. The quotation marks MUST stay. Be sure 
     to read the notes under each $variable.
  2. Save the edits.
  3. Upload your modified /missing_images.php to your store directory. (It 
     does not have to be in the root of the store directory but it does 
     have to be on the same server as it.)
  4. Run the script via web browser.

Un-Install
==========
1. Delete all files that were copied from the installation package.
