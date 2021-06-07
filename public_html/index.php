<?php
require_once("header_page.php");
require_once("backend.php");
?>

<div class="wrapper container">
  <header></header>
<nav></nav>
<div class="heading"></div>
   <div class="row">
          <aside class="col-md-7">
  <ul class="nav nav-tabs">
    <li class="nav-item">
      <a class="nav-link active" data-toggle="tab" href="#home">Home</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-toggle="tab" href="#screenshots">Screenshots</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-toggle="tab" href="#download">Download</a>
    </li>  
    <li class="nav-item">
      <a class="nav-link" data-toggle="tab" href="#faq">Faq</a>
    </li>  
    <li class="nav-item">
      <a class="nav-link" data-toggle="tab" href="#support">Support</a>
    </li>
    <li class="nav-item">
      <a class="nav-link" data-toggle="tab" href="#thanks">Thanks</a>
    </li>  
  </ul>
  <div id="myTabContent" class="tab-content">
    <div class="tab-pane fade active show" id="home">
<h1>Hello and welcome to our small home page!</h1>

<h2>Intro</h2>
You must be fimiliar with the abandonware acronym already if you found this website. :)<br>
I've been interested in abandonware for decades, have tons of old hardware and software, but i've faced one big problem, having too much of stuff, it's not easy to transfer files between them!
One day i just wanted to run DosBox with few games on it and it took some time to find correct .zip packages, extract them and load to some kind of existing DosBox "frontends" or "helpers". It's pain in ass to manage big software library, find something by some criteria or use something instantly lauching DosBox without tons of command line commands. But what if i want to transfer my files to the old pc? Do i need to use floppy, or maybe lan? Damn in that era, pc's didn't have any of ethernet cards or ports by default, I need some external connected pci or pcmcia card to use network :/
But if I have some much more hardware stuff, lets say 5 pc's that runs MS-DOS 6.22 without windows or lan drivers and floppy disk drives are broken also? Yes I can use some kind rs232 file transfer using terminal connected via serial cable. 
It's not very nice way to do that especially when you run modern OS like MacOS that has some issues with different USB to serial cable converters. What i'm trying to say? 
Well i've just thouth if i can make some easy way to manage that large software stuff by implementing one central storage for all software/games and make one application that works in cross-platform with every big OS on the market, i mean MacOS, Windows and Linux. 
Yes it's done already! Also as experimental feature i've implemented rs232 file transfer to your old pc using RaspberryPI as a bridge with serial connection. The application uses standard ssh method to put files to RaspberryPI then upload to any rs232 capable MS-DOS computer that have <a href="http://www.columbia.edu/kermit/ck90.html" target="_blank">Kermit</a> software installed! And yes, that works pretty well.
As you see in the left corner, we have plenty of data already, more will come later. Also there are no limitations on supporting other platforms such as Amiga, Amstrad CPC and much more.

    </div>
    <div class="tab-pane fade" id="screenshots">
<h2>Windows</h2>
<img src="screens/windows/Screenshot_5.png" width="600px"/>
<img src="screens/windows/Screenshot_6.png" width="600px"/>
<img src="screens/windows/Screenshot_7.png" width="600px"/>
<h2>MacOS</h2>
<img src="screens/osx/Screenshot2020-09-03at10.01.43.png" width="600px"/>
<img src="screens/osx/Screenshot2020-09-03at10.02.05.png" width="600px"/>
<img src="screens/osx/Screenshot2020-09-03at10.02.40.png" width="600px"/>
<img src="screens/osx/Screenshot2020-09-03at10.04.32.png" width="600px"/>
<img src="screens/osx/Screenshot2020-09-03at10.05.10.png" width="600px"/>
<h2>Linux</h2>
Coming soon!
    </div>
    <div class="tab-pane fade" id="download">

<button type="button" class="btn btn-primary btn-lg" onclick="window.open('builds/latest-windows-x86.zip')">Windows x32</button><button type="button" class="btn btn-primary btn-lg" onclick="window.open('builds/latest-windows-x64.zip')">Windows x64</button>
<button type="button" class="btn btn-primary btn-lg" onclick="window.open('builds/latest-linux-x64.zip')">Linux</button>
<button type="button" class="btn btn-primary btn-lg" onclick="window.open('builds/latest-mac-x64.zip')">Mac</button>
      </div>
 <div class="tab-pane fade" id="faq">
<? require_once("faq_page.php") ?>
</div>

    <div class="tab-pane fade" id="support">
Feature requests, bugs and hugs go to e1z0 [at] abandonware [dot] club
</div>
    <div class="tab-pane fade" id="thanks">
Thanks to everyone who do not disturbed the development
    </div>
  </div>

<br>
          </aside>
<!--          <section class="col-md-17">-->
<div class="col-lg-4">
  <div class="bs-component">
    <div class="card border-success mb-3">
      <div class="card-header" id="gameheader">Welcome</div>
      <div class="card-body">
        <h4 class="card-title" id="brief">Statistics</h4>
        <ul class="list-group" id="details">
<?php
if ($redis->exists("abandonware_stats")) {
$stats = json_decode($redis->get("abandonware_stats"));
foreach ($stats as $key) {
echo '   <li class="list-group-item d-flex justify-content-between align-items-center">
                  '.$key->info.'
                  <span class="badge badge-primary badge-pill">'.$key->val.'</span>
                </li>';
}

}
?>
        </ul>
      </div>
    </div>
      </div>
    </div>
<!--          </section> -->
        </div>
      <footer></footer>
    












</div>
<?php
require_once("footer_page.php");

?>
