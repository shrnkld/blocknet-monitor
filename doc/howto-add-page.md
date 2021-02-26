# Howto add a new page

## A short guide for contributors wanting to add a new page to the Blocknet Monitor.

You need some basic familiarity with PHP and HTML.
There's a good chance you can base your new page and the data generation routine
off existing code using a generous helping of copy'n'paste.  

If you want to be able to share your new page with the community you should also 
be familiar with Git and Github. 
If you don't plan to share the code you can skip all the Git instructions below.


1. Clone the Blocknet Monitor repository (or your own fork of it).
```
git clone https://github.com/walkjivefly/blocknet-monitor.git
cd blocknet-monitor
git checkout -b feature-newpage
```
2. Add a section to src/Content.php to generate the data for your page.
3. Create views/newpage.phtml to display the data.
4. Add a section to index.php to call your data generation code.
5. Add a link to your new page in src/header.php
6. Test the new page in a private instance of the Blocknet Monitor.
7. Tweak until you're happy with it.
8. Commit the changes to your local repository.
9. Push your branch to your Github repository.
10. Submit a merge request.
