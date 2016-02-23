<?php

$ARTICLES_PER_EMAIL = 4;
$ARTICLES_PER_SUBREDDIT = 3;

function str_replace_first_n($from, $to, $subject, $n)
{
    $from = '/'.preg_quote($from, '/').'/';
    return preg_replace($from, $to, $subject, $n);
}


#styli
$rssStyle =" <style>
 { margin:0; padding:0; }
p { padding: .5em 0; }
h1,h2,h3,h4,h5,h6 { font-size: 1em; padding: .5em 0; }
html { display:block; padding-bottom:50px; }
body { font:80% Verdana, sans-serif; color:#000; padding:25px 0 0 35px; }
a { color:#5BAB03; text-decoration:none; }
a:hover { color:#5BAB03; text-decoration: underline;}
ul { margin-left:1.5em; }
li { margin-bottom:0.4em; }
div#content>ul { list-style-type: none; }
div.article>li>a { font-weight:bold; font-size: 1.3em;}
img{
  max-width: 90%;
}
div { line-height:1.6em; }
div#content { background:#fff; margin-right:15px; padding-left:1em;}
div#content div { margin:0 1em 1em 0; }
div#explanation { padding:1em 1em 0 1em; border:1px solid #ddd; background:#efefef; margin:0 2em 2em 0; }
div#explanation h1 { font-weight:normal; font-size:1.8em; margin-bottom:0.3em; }
div#explanation p { margin-bottom:1em; }
.small { font-size: .7em; color: #666; }
</style>
";

#In the candidate array, put any subreddits that you want to be pulled nondeterministically 
$candidateArray = array("funny", "explainlikeimfive", "askscience", "lifeofnorman", "relationships");
$billArray = array();
$billArray1 = array();

#In billArray2, put any subreddits that you want to be in every edition of billfeed
$billArray2 = array("news", "youshouldknow", "lifeprotips", "losangeles", "outoftheloop");


#this is so hacky, oh my god
$existingNumbers = array();
for($x = 0; $x < $ARTICLES_PER_EMAIL; $x++){
  $random = rand(0, count($allArray) - 1);
  if(!in_array($random, $existingNumbers)){
    array_push($existingNumbers, $random);
    array_push($billArray, $allArray[$random]);
  }
  else{
    $x -= 1;
  }
}
$existingNumbers = array();
for($x = 0; $x < $ARTICLES_PER_EMAIL; $x++){
  $random = rand(0, count($allArray) - 1);
  if(!in_array($random, $existingNumbers)){
    array_push($existingNumbers, $random);
    array_push($billArray1, $allArray[$random]);
  }
  else{
    $x -= 1;
  }
}
$metaArray = array($billArray2, $billArray, $billArray1);

#separated by commas, insert the email addresses that you want the feed to go. can be the same. it's split up like this because larger emails suffer delays in transit
$emailArray = array("hi@example.com", "hi@example.com", "hi@example.com");
for($g = 0; $g < count($metaArray); $g++){
  $mensaje = $rssStyle;
  for($x = 0; $x < count($metaArray[$g]); $x++){
    $subredditArray = $metaArray[$g];
    $subredditURL = "http://www.reddit.com/r/" . $subredditArray[$x] . ".rss";
    $page = file_get_contents($subredditURL);
    $page = simplexml_load_string($page);
    $counter = $ARTICLES_PER_SUBREDDIT;
    for($y = 0; $y < $counter; $y++){
      $time = strtotime($page->entry[$y]->updated);
      $timeDiff = (time() - $time);
      if($counter == 10) break;
      if($timeDiff < 259200){
        $mensaje .= ("<b>" . $subredditArray[$x] . "</b><br/>");
        echo("<h2>" . $page->entry[$y]->title . "</h2><br/><br/>");
        $mensaje .= ("<b>" . $page->entry[$y]->title . "</b><br/>");
        $moneyShot = $page->entry[$y]->content;
        $moneyShot = "<hi>" . $moneyShot;
        $moneyShot = $moneyShot . "</hi>";
        $moneyShot = str_replace("&nbsp;", "", $moneyShot);
        $moneyShot = simplexml_load_string($moneyShot);
        $directRedditContent = $moneyShot->xpath("//div");
        if(count($directRedditContent) > 0){
          $mensaje .= ($directRedditContent[0]->asXML());
          $mensaje .= "<br/>Top Comment <br/>";
        }
        $list = $moneyShot->xpath("//a[text()='[link]']/@href");
        $url = urlencode($list[0]);

        #this will break it if anyone besides you tries to use it. might be worth explaining in the future
        $url = str_replace(".", "HAIGUYZ", $url);
        echo($url . "<br/><br/>");

        #insert url for makefulltextfeed. i used a downloaded copy on my server, but you can use their hosted version
        $domain = "INSERT_URL_HERE";

        $homepage = file_get_contents($domain . $url);
        if($homepage != "URL blocked" && (strpos($url, "HAIGUYZnewsHAIGUYZcomHAIGUYZau") === false)){
          $homepage = simplexml_load_string($homepage);
          $content = $homepage->channel->item->description;
          $content = str_replace("This entry passed through the Full-Text RSS service - if this is your content and you're reading it on someone else's site, please read the FAQ at fivefilters.org/content-only/faq.php#publishers.", "<br/><br/>Next Article<br/><br/>" , $content);
          if(strlen($content) < 20000 ){
            $mensaje .= $content;
            $mensaje .= "<hr color='#00CC33' size='5'/> ";
          }
          else{
            $mensaje .= "This article was so long that it will screw up formatting and, let's face it, you probably wouldn't have read it anyway.<br/>";
            $mensaje .= "<hr color=#00CC33 size=5/> ";
            $counter++; 
          }
        }
        else{
          $mensaje .= "The news source blocked billfeed.  What jerks!<br/>";
          $mensaje .= "<hr color=#00CC33 size=5/> ";
          $counter++;
        }
      }
      else{
        $counter++;
      }
    }
    #strips our undesirable elements
    $mensaje = str_replace("href", "mydogsnose", $mensaje);
    $mensaje = str_replace("<img", "<img style='max-width: 85%;' ", $mensaje);
    $mensaje = str_replace("textarea", "TEXTAREA_ERROR", $mensaje);
    $mensaje = str_replace("iframe", "IFRAME_ERROR", $mensaje);
    $mensaje = str_replace("https://www.youtube.com", "YOUTUBE_ERROR", $mensaje);
    $mensaje = str_replace("video", "video_", $mensaje);
  }
  $to = $emailArray[$g];
  $subject = "BillFeed V2.0 Daily Digest: " . date('l jS \of F Y h:i:s A');
  $message = $mensaje;
  echo $message;
  //$message = $content . $rssStyle;
  // Always set content-type when sending HTML email
  $headers = "MIME-Version: 1.0" . "\r\n";
  $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
  // More headers
  $headers .= 'From: <billfeed@billdriscoll.net>' . "\r\n";
  //$headers .= 'Cc: myboss@example.com' . "\r\n";
  mail($to,$subject,$message,$headers);
}
echo "we sent it baybeeeeee"
  
?>
