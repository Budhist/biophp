<!--
Title: GC, AT, KETO and Oligo-Skews
Author: Joseba Bikandi
License: GNU GLP v2
-->

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <title>GC, AT, KETO and Oligo-Skews</title>
</head>
<body style="background-color: rgb(255, 255, 255);">
<center>

<?php
if($_SERVER["QUERY_STRING"]=="info"){
        print_info ();
        die();
}
if(!$_POST){
        print_form();
        die();
}

// If here, info has been submited

// Set maximum amount of seconds the script will run to 1,000 seconds. In many cases,
//   the default will be 30 seconds, which is not enought in most cases for this script
set_time_limit (1000);
// avoid reporting errors
error_reporting(0);

// GET DATA
    // size of window
        // $window = window size from select
        $window=$_POST["window"];
        // $window = custom window size from user
        $window2=$_POST["window2"];
        // custom window size id not submited, use the $window value
        if ($window2!=""){$window=$window2;}
        // check whether $window is in the correct range
        if ($window<100 or $window>50000){die("Window size is not within allowed range: 100 to 50000 bases");}
    // type of skews to create; if value is 1, it will be computed
        $GC=$_POST["GC"];       // for GC-skew
        $AT=$_POST["AT"];       // for AT-skew
        $KETO=$_POST["KETO"];   // for KETO-skew
        $GmC=$_POST["GmC"];     // for G+C skew
    // get name of sequence
        $name=$_POST["name"];
        // if name is not specified, name is "sequence"
        if($name==""){$name="sequence";}
    // get sequence
        $sequence=strtoupper($_POST["seq"]);
        // remove non-coding
        $sequence=preg_replace("/\W|\d/","",$sequence);
        // is sequence is over 10 MB long, display error
        if(strlen($sequence)>10000000){die("Error: maximum lengt of sequence allowed is 10,000,000 bp. ");}
    // if subsequence of input sequence must be used
        // get start and end
        $from=$_POST["from"];
        $to=$_POST["to"];
        // remove useless part of sequence
        if ($from or $to){
                if(str_is_int($to)==1){$sequence=substr($sequence,0,$to);}
                if(str_is_int($from)==1){$sequence=substr($sequence,$from);}
        }
    // if sequence does not exists, display error
        if ($sequence==""){die("Error: no sequence selected for computing");}
    // if sequence is to sort to work with, display error
        if(strlen($sequence)<($window+1400)){die("Error: sequence is very small for the selected window size.");}
    // when oligo-skew is requested, computing time will be long; let know the user and compute data for oligo-skew
        if ($_POST["oskew"]==1){
                if(str_is_int($_POST["oligo_len"])==1){
                        print "Computing...(will be aborted after 15 minutes; oligo-skews require intense computing). Please wait. ";flush();
                        // in next line a funstion will compute an array with distances
                        $oligo_skew_array=Oligo_skew_array_calculation($sequence,$window,$_POST["oligo_len"],$_POST["strands"]);
                }
        }
   // create image with skews
        $data_table=Create_image($sequence,$window,$GC,$AT,$KETO,$GmC,$oligo_skew_array,$_POST["oligo_len"], $from, $to, $name);


   // in next lines, the image is shown
    ?>
        <h1>Skew from custom sequences</h1>
        <p>
        <img src="image.png?<? print date("U"); ?>" border=0 width=850 height=450 ismap=ismap align=top>

        </center>
        </body>
        </html>

    <?

// #######################################################################################
// ##############################       FUNCTIONS     ####################################
// #######################################################################################
function print_info(){
        // prints the information... nothing more
        ?>
        <h1>Skews from custom sequences</h1>

        <table width=900 cellpadding=10 cellspacing=2><tr><td style="vertical-align: top;">

        To compute skews, the input sequence is scanned with a sliding window, and the requested parameter is calculate
        for each window. The data is shown in a graph.

        <P><big><big><b>G+C %</b></big></big>
        <br>The porcentaje of G+C is computed

        <p><big><big><b>GC-skew</b></big></big>
        <br>For each window, (G-C)/(G+C) is computed, where G and C are the number of ocurrences for each nucleotide

        <p><big><big><b>AT-skew</b></big></big>
        <br>For each window, (A-T)/(A+T) is computed, where A and T are the number of ocurrences for each nucleotide.

        <p><big><big><b>KETO-skew</b></big></big>
        <br>For each window, (G+C-A-T)/(G+C+A+T) is computed, where A, C, G and T are the number of ocurrences for each nucleotide.

        <p><big><big><b style="font-weight: bold;">Oligo-skew</b></big></big>
        <br>For each window, frecuencies for all nucleotides with selected length is computed.
        Those frecuencies are compared to frecuencies obtained from the complete sequence.
        When comparing both frecuencies, global distance is computed, as described by
        <a href="http://www.ncbi.nlm.nih.gov/entrez/query.fcgi?cmd=Retrieve&amp;db=pubmed&amp;dopt=Abstract&amp;list_uids=11331237">Almeida et al. (2001)</a>.&nbsp; </p>

        </td></tr></table>
        </center>
        </body>
        </html>
        <?
}
// #######################################################################################
function print_form(){
        // prints the form with all the options
        ?>
        <table cellpadding=5>
        <tr><td align=center>

        <h1>Skews from custom sequences</h1>

        </td></tr><td bgcolor=DDDDFF>

        <form action="<? print $_SERVER["PHP_SELF"]; ?>" method="post">
        Name of sequence: <input type=text name=name size=20>
        <br>Copy sequence in the textarea below (up to 5,000,000 bp):<br>
        <textarea cols="80" rows="3" name="seq"></textarea><br>

        <p> Window size
        <select name=window><option value="">Choose size
        <option>5000
        <option>10000
        <option>50000
        </select>  or <input type=text name=window2 size=7> bases(100-50000)

        <p><input value="Show new graph" type="submit">

        <hr> Show <input type=checkbox name=GmC value=1 checked> G+C %
        <br> Show <input type=checkbox name=GC value=1> GC-skew
        <br> Show <input type=checkbox name=AT value=1> AT-skew
        <br> Show <input type=checkbox name=KETO value=1> KETO-skew
        <br>Show <input type=checkbox name=oskew value=1> oligo-skew
        for
        <select name=oligo_len>
        <option value=2>dinucleotides
        <option value=3>trinucleotides
        <option value=4 selected>tetranucleotides
        <option value=5>pentanucleotides
        <option value=6>hexanucleotides
        </select>
        in <select name=strands><option value=1>one strand<option value=2>both strands</select>

        <hr>
        Show subsequence: from <input name="from" type="text" size=5> to <input name="to" type="text" size=5>

        </form>

        </td></tr><tr><td align=right>

        <a href=?info>Info</a>
        
        </td></tr><tr><td>

        This is simplified script extracted from the online tool at <a href=http://insilico.ehu.es/oligoweb/skews/>insilico.ehu.es/oligoweb/skews/</a>.
        <br>GC-, AT- and oligo-skews images for all sequenced bacterial genomes are available <a href=http://insilico.ehu.es/oligoweb/>here</a>
        <p>Source code is available at <a href=http://www.biophp.org/minitools/skews/>BioPHP.org</a>

        </td></tr></table>
        </center>
        </body>
        </html>
        <?
}
// #######################################################################################
function Oligo_skew_array_calculation($sequence,$window,$oskew,$strands){
        // will  compare oligonucleotide frequencies in all the sequence
        //    with frequencies in each window, and will return an array
        //    with distances  (computed as Almeida et al, 2001).
        
        // search for oligos in the complet sequence
        $tetra_arrayA=search_oligos($sequence,$oskew);
        $seq_len= strlen($sequence);
        $period=ceil($seq_len/1400);
        if($period<10){$period=10;}
        if ($strands==2){
           // if both strands are used for computing oligonucleotide frequencies
           $sequence2=Comp($sequence);
           $i=0;
           while ($i<$seq_len-$window+1){
                $cadena=substr($sequence,$i,$window)." ".strrev(substr($sequence2,$i,$window));
                // compute oligonucleotide frequencies in window
                $tetra_arrayB=search_oligos($cadena,$oskew);
                // compute distance between complete sequence and window
                $data[$i]=distance($tetra_arrayA,$tetra_arrayB);
                $i+=$period;
            }
        }else{
           // if only one strand is used for computing oligonucleotide frequencies
           $i=0;
            while ($i<$seq_len-$window+1){
                $cadena=substr($sequence,$i,$window);
                // compute oligonucleotide frequencies in window
                $tetra_arrayB=search_oligos($cadena,$oskew);
                // compute distance between complete sequence and window
                $data[$i]=distance($tetra_arrayA,$tetra_arrayB);
                $i+=$period;
            }
        }
        // return the array with distances
        return $data;
}
// #######################################################################################
function search_oligos($cadena,$len_oligos){
        // search for frequencies of oligonucleotides of len $len_oligos,
        // and returns results in an array
        $i=0;
        $len=strlen($cadena)-$len_oligos+1;
        while ($i<$len){
            $seq=substr($cadena,$i,$len_oligos);
            $oligos_internos[$seq]++;
            $i++;
        }
        $base_a=array("A","C","G","T");
        $base_b=array("A","C","G","T");
        $base_c=array("A","C","G","T");
        $base_d=array("A","C","G","T");
        $base_e=array("A","C","G","T");
        $base_f=array("A","C","G","T");
        //for oligos 2 bases long
        if ($len_oligos==2){
            foreach($base_a as $key_a => $val_a){
                        foreach($base_b as $key_b => $val_b){
                         if ($oligos_internos[$val_a.$val_b]){
                                $oligos[$val_a.$val_b] = $oligos_internos[$val_a.$val_b];
                        }else{
                                $oligos[$val_a.$val_b] = 0;
                        }
                        }}
        }
        //for oligos 3 bases long
        if ($len_oligos==3){
                        foreach($base_a as $key_a => $val_a){
                        foreach($base_b as $key_b => $val_b){
                        foreach($base_c as $key_c => $val_c){
                        if ($oligos_internos[$val_a.$val_b.$val_c]){
                                $oligos[$val_a.$val_b.$val_c] = $oligos_internos[$val_a.$val_b.$val_c];
                        }else{
                                $oligos[$val_a.$val_b.$val_c] = 0;
                        }
                        }}}
        }
        //for oligos 4 bases long
        if ($len_oligos==4){
                        foreach($base_a as $key_a => $val_a){
                        foreach($base_b as $key_b => $val_b){
                        foreach($base_c as $key_c => $val_c){
                        foreach($base_d as $key_d => $val_d){
                            if ($oligos_internos[$val_a.$val_b.$val_c.$val_d]){
                                $oligos[$val_a.$val_b.$val_c.$val_d] = $oligos_internos[$val_a.$val_b.$val_c.$val_d];
                            }else{
                                $oligos[$val_a.$val_b.$val_c.$val_d] = 0;
                            }
                        }}}}
        }
        //for oligos 5 bases long
        if ($len_oligos==5){
                        foreach($base_a as $key_a => $val_a){
                        foreach($base_b as $key_b => $val_b){
                        foreach($base_c as $key_c => $val_c){
                        foreach($base_d as $key_d => $val_d){
                        foreach($base_e as $key_e => $val_e){
                            if ($oligos_internos[$val_a.$val_b.$val_c.$val_d.$val_e]){
                                $oligos[$val_a.$val_b.$val_c.$val_d.$val_e] = $oligos_internos[$val_a.$val_b.$val_c.$val_d.$val_e];
                            }else{
                                $oligos[$val_a.$val_b.$val_c.$val_d.$val_e] = 0;
                            }
                        }}}}}
        }
        //for oligos 6 bases long
        if ($len_oligos==6){
                        foreach($base_a as $key_a => $val_a){
                        foreach($base_b as $key_b => $val_b){
                        foreach($base_c as $key_c => $val_c){
                        foreach($base_d as $key_d => $val_d){
                        foreach($base_e as $key_e => $val_e){
                        foreach($base_f as $key_f => $val_f){
                            if ($oligos_internos[$val_a.$val_b.$val_c.$val_d.$val_e.$val_f]){
                                $oligos[$val_a.$val_b.$val_c.$val_d.$val_e.$val_f] = $oligos_internos[$val_a.$val_b.$val_c.$val_d.$val_e.$val_f];
                            }else{
                                $oligos[$val_a.$val_b.$val_c.$val_d.$val_e.$val_f] = 0;
                            }
                        }}}}}}
        }
        $counter=0;
        foreach($oligos as $key => $val){$oligos2[$counter]=$val; $counter++;}
        return $oligos2;

}
// #######################################################################################
function distance($vals_x,$vals_y){
        // computes disatcne between two arrays of values based in Almeida et al, 2001
        //   http://www.ncbi.nlm.nih.gov/entrez/query.fcgi?cmd=Retrieve&db=pubmed&dopt=Abstract&list_uids=11331237
        //   (which is a based in a modified Pearson correlation)
        if (sizeof($vals_x)!= sizeof($vals_y)){return;}
        $nw=0;
        $x2y=0;
        $xy2=0;
        $pre_sx=0;
        $pre_sy=0;
        $pre_rw=0;
        $n=sizeof($vals_x);
        foreach($vals_x as $key => $val_x){
                $val_y=$vals_y[$key];
                $nw+=$val_x*$val_y;
                $x2y+=$val_x*$val_x*$val_y;
                $xy2+=$val_x*$val_y*$val_y;
        }
        $xw=$x2y/$nw;
        $yw=$xy2/$nw;
        foreach($vals_x as $key => $val_x){
                $val_y=$vals_y[$key];
                $pre_sx+=pow($val_x-$xw,2)*$val_x*$val_y;
                $pre_sy+=pow($val_y-$yw,2)*$val_x*$val_y;
        }
        $sx=$pre_sx/$nw;
        $sy=$pre_sy/$nw;
        foreach($vals_x as $key => $val_x){
                $val_y=$vals_y[$key];
                $pre_rw+=($val_x-$xw)*($val_y-$yw)*$val_x*$val_y/(sqrt($sx)*sqrt($sy));
        }
        $rw=$pre_rw/$nw;
        return round(1-$rw,8);
}

// #######################################################################################
function str_is_int($str) {
        $var=intval($str);
        return ("$str"=="$var");
}
// #######################################################################################
// CREATE IMAGEN
function Create_Image($sequence,$window,$GC,$AT,$KETO,$GmC,$oligo_skew_array,$olen,$from,$to,$name){
        // creates the image based in data provided
        // the code is not properly explained yet
        $pos=0;
        $len_seq=strlen($sequence);
        $period=ceil($len_seq/6000);
        
        // computes data for GC, AT, KETO and G+C skews (if requested)
        while ($pos<$len_seq-$window){
                $sub_seq=substr($sequence,$pos,$window);
                $A=substr_count($sub_seq,"A");$C=substr_count($sub_seq,"C");$G=substr_count($sub_seq,"G");$T=substr_count($sub_seq,"T");
                $dGC[$pos]=($G-$C)/($G+$C);
                if ($AT==1){$dAT[$pos]=($A-$T)/($A+$T);}
                if ($KETO==1){$dKETO[$pos]=round(($G+$C-$A-$T)/($A+$C+$G+$T),4);}
                if ($GmC==1){$dGmC[$pos]=($G+$C)/($A+$C+$G+$T);}
                $pos+=$period;
        }

        // scale related variables
        $max=max(max($dAT),max($dGC),max($dKETO));
        $min=min(min($dAT),min($dGC),min($dKETO));
        $nmax=max($max,-$min);
        $rectify=round(200/$nmax);

        // starts the image
        $im = @imagecreate(850, 450) or die("Cannot Initialize new GD image stream. It is GD library available?");
        $background_color =imagecolorallocate($im, 255, 255, 255);
        $black=ImageColorAllocate($im, 0, 0, 0);
        $qblack2=ImageColorAllocate($im, 228, 228, 228);
        $qblack=ImageColorAllocate($im, 192, 192, 192);
        $red=ImageColorAllocate($im, 255, 0, 0);
        $blue=ImageColorAllocate($im, 0, 0, 255);
        $green=ImageColorAllocate($im, 0, 255, 0);
        $rb=ImageColorAllocate($im, 255, 0, 255);
        $gb=ImageColorAllocate($im, 0, 150,150);
        imagestring($im, 2, 610, 432,  "by biophp.org", $black);
        imagestring($im, 3, 600, 5,  "Window: $window", $black);

        // writes length of sequence
        if ($from or $to){
                if(!$from){$from=0;}
                if(!$to){$to=$len_seq;}
                imagestring($im, 3, 5, 432,  "Length of $name: $len_seq (from position $from to $to)", $black);
        }else{
                imagestring($im, 3, 5, 432,  "Length of $name: $len_seq", $black);
        }

        // write the kind of skews in proper color
        $goright=0;
        if ($GC==1){
                imagestring($im, 3, 5+$goright, 5,  "GC-skew", $blue);
                $goright=70;}
        if ($AT==1){
                imagestring($im, 3, 5+$goright, 5,  "AT-skew", $red);
                $goright+=70;}
        if ($KETO==1){
                imagestring($im, 3, 5+$goright, 5,  "KETO-skew", $green);
                $goright+=80;}
        if ($GmC==1){
                imagestring($im, 3, 5+$goright, 5,  "G+C", $black);
                $goright+=60;}
        if (sizeof($oligo_skew_array)>10){
                imagestring($im, 3, 5+$goright, 5,  "oligo-skew ($olen)", $gb);}

       // print scale for AT, GC or KETO skews
        $ne=0;
        if($AT==1 or $GC==1 or $KETO==1){
                imagestring($im, 3, 710, 210,  "0", $red);
                $scale=round($nmax*0.25,3);
                $v=$scale*3;
                        imagestring($im, 3, 710, 60,  $v, $red);
                        imagestring($im, 3, 710, 360,  -$v, $red);
                $v=$scale*2;
                        imagestring($im, 3, 710, 110,  $v, $red);
                        imagestring($im, 3, 710, 310,  -$v, $red);
                $v=$scale;
                        imagestring($im, 3, 710, 160,  $v, $red);
                        imagestring($im, 3, 710, 260,  -$v, $red);
                $ne=60;
        }
        // print scale for G+C skew
        if($GmC==1){
                $kkk=360;
                for($i=20;$i<81;$i+=10){imagestring($im, 3, 710+$ne, $kkk,  "$i%", $black);$kkk-=50;}
                if($ne==60){
                        for($i=20;$i<421;$i+=50){imageline($im,698+$ne,$i,703+$ne,$i,$black);}
                        imageline($im,764,20,764,420,$black);
                }
                $ne+=60;
        }
        // print scale for oligo-skew
        if(sizeof($oligo_skew_array)>10){
                $kkk=15;
                for($i=0;$i<9;$i++){imagestring($im, 3, 710+$ne, $kkk,  "0.$i", $gb);$kkk+=50;}
                if($ne>0){
                        for($i=20;$i<421;$i+=50){imageline($im,698+$ne,$i,703+$ne,$i,$black);}
                        imageline($im,704+$ne,20,704+$ne,420,$black);
                }
        }
        // print oligo-skew
        //   oligo-skews must be the first one to be printed out
        $xp=($window*700)/(2*$len_seq);
        if (sizeof($oligo_skew_array)>10){
                foreach($oligo_skew_array as $pos => $val){
                        $x=round(($pos*700/$len_seq)+$xp);
                        imageline($im,$x,20,$x,19+(500*$val),$qblack2);
                        imagesetpixel($im,$x,20+(500*$val),$gb);
                }
        }
        // print AT, GC and/or KETO-skews
        //    each one with its color
        foreach($dGC as $pos => $val){
                $x=round(($pos*700/$len_seq)+$xp);
                if($AT==1){imagesetpixel($im,$x,220-$dAT[$pos]*$rectify,$red);}
                if($GC==1){imagesetpixel($im,$x,220-$val*$rectify,$blue);}
                if($KETO==1){imagesetpixel($im,$x,220-$dKETO[$pos]*$rectify,$green);}
                if($GmC==1){imagesetpixel($im,$x,470-(500*$dGmC[$pos]),$black);}
        }

        // write some aditional lines
        for($i=20;$i<421;$i+=50){imageline($im,0,$i,700,$i,$black);}
        imageline($im,70,20,70,420,$qblack);
        imageline($im,140,20,140,420,$qblack);
        imageline($im,210,20,210,420,$qblack);
        imageline($im,280,20,280,420,$qblack);
        imageline($im,350,20,350,420,$qblack);
        imageline($im,420,20,420,420,$qblack);
        imageline($im,490,20,490,420,$qblack);
        imageline($im,560,20,560,420,$qblack);
        imageline($im,630,20,630,420,$qblack);
        imageline($im,700,20,700,420,$black);

        // output the image to a file
        imagepng($im,"image.png");
        imagedestroy($im);
        return;
}
// #######################################################################################
function Comp($code){
        // returns complement of sequence $code
        $code=str_replace("A", "t", $code);
        $code=str_replace("T", "a", $code);
        $code=str_replace("G", "c", $code);
        $code=str_replace("C", "g", $code);
        return strtoupper ($code);
}
?>
