<!--
Title: Distance among sequences: compute global distance and show dendrogram
Author: Joseba Bikandi
License: GNU GLP v2
-->

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
  <title>Comparison of oligonucleotide composition in sequences</title>
</head>
<body bgcolor=FFFFFF>
<center>

<?php

// nothing has been posted -> show form and die
        if (!$_POST){print_form(); die();}

// something has been posted -> go ahead
set_time_limit (10000);
error_reporting(0);

// to show computing period,
$timestart=date("U");

// GET SEQUENCES
        // get data from form
        $allsequences=$_POST["seq"];
        // set a limit for maximum length of data posted
        if (strlen($allsequences)>2000000){
                die("Error: this service does not handle input requests longer than 2,000,000 bp.");
        }

        //remove a couple of things from sequence
        $allsequences=substr($allsequences,strpos($allsequences,">"));      // whatever is before ">", which is the start of the first sequence
        $allsequences=preg_replace("/\r/","",$allsequences);            // remove carriage returns ("\r"), but do not remove line feeds ("\n")

        // split the individual sequences into $seqs array
        $seqs=preg_split("/>/",$allsequences,-1,PREG_SPLIT_NO_EMPTY);

        // get the name of each sequence (save names to array $seq_name)
        foreach ($seqs as $key => $val){
                $seq_name[$key]=substr($val,0,strpos($val,"\n"));
                $temp_val=substr($val,strpos($val,"\n"));
                $temp_val=preg_replace("/\W|\d/","",$temp_val);
                $seqs[$key]=strtoupper($temp_val);
        }
        // at this moment two arrays are available: $seqs (with sequences) and $seq_names (with name of sequences)

// COMPUTE DISTANCES
        // GET METHOD

        if ($_POST["method"]=="euclidean"){         // EUCLIDEAN DISTANCE
                // COMPUTE OLIGONUCLEOTIDE FREQUENCIES
                foreach($seqs as $key => $val){
                        // to compute oligonucleotide frequencies, both strands are used
                        $seq_and_revseq=$val." ".RevComp($val);
                        $oligo_array[$key]= oligo_frequencies_standar($seq_and_revseq,$_POST["len"]);
                }
                // COMPUTE DISTANCES AMONG SEQUENCES
                //    by computing Euclidean distance
                //    standarized oligonucleotide frequencies in $oligo_array are used, and distances are stored in $data array
                foreach($seqs as $key => $val){
                        foreach($seqs as $key2 => $val2){
                                if ($key>=$key2){continue;}
                                $data[$key][$key2]= Euclid_distance($oligo_array[$key],$oligo_array[$key2],$_POST["len"]);
                        }
                }
        }else{
                // COMPUTE OLIGONUCLEOTIDE FREQUENCIES
                foreach($seqs as $key => $theseq){
                        $oligo_array[$key]= compute_zscores_for_tetranucleotides($theseq);
                }
                // COMPUTE DISTANCES AMONG SEQUENCES
                //    by computing Pearson distance
                //    standarized oligonucleotide frequencies in $oligo_array are used, and distances are stored in $data array
                foreach($seqs as $key => $val){
                        foreach($seqs as $key2 => $val2){
                                if ($key>=$key2){continue;}
                                $data[$key][$key2]= Pearson_distance($oligo_array[$key],$oligo_array[$key2]);
                        }
                }

        }




// DISPLAY TABLE WITH DISTANCES
        print "<table border=1>\n<tr><td bgcolor=DDDDFF width=75>Distances</td>";
        for($i=1;$i<sizeof($oligo_array);$i++){
                print "<td bgcolor=DDDDFF width=75>$i</td>";
        }
        print "</tr>\n";
        foreach($data as $key => $val){
                print "<tr><td bgcolor=DDDDFF width=75>$key</td>";
                for($i=1;$i<sizeof($oligo_array);$i++){
                        if ($data[$key][$i]){
                                $aa=floor(255*($data[$key][$i]));
                                print "<td style=\"background-color: rgb(255,$aa,$aa);\">".$data[$key][$i]."</td>";
                        }else{
                                print "<td>&nbsp;</td>";
                        }
                }
                print "</tr>\n";
        }
        print "</table>\n";

// DISPLAY NAME OF SEQUENCES
        print "<table><tr><td>\n<p><b>Name of sequences</b>\n<pre>\n";
        foreach($seq_name as $n => $name){
                print "  $n\t$name\n";
        }
        print "</pre></td></tr></table>\n";

// DISPLAY SELECTED OLIGONUCLEOTIDE LENGTH
        print "Length of oligoncleotides for comparison: ".$_POST["len"];


// NEXT LINES WILL PERFORM UPGMA CLUSTERING
        // in each loop, array $data is reduced (one case per loop)
        while (sizeof($data)>1){
                $min=min_array($data);   // global variables are created: $x, $y, $min, $cases
                $comp[$x][$y]=$min;
                $data=new_array($data,$cases,$x,$y);
        }
        $min=min_array($data);
        $comp[$x][$y]=$min;
        // end of clustering
        //  array $comp stores the important data

        // $textcluster is the results of the cluster as text.
        //     p.e.:  ((3,4),7),(((5,6),1),2)
        $textcluster="$x,$y";
        print "<p>Clustering method: UPGMA<BR>$textcluster<p>";

        // $max is the distance in the last clustering step (the root of the dendrogram)
        $max=$a[$x][$y];

// CREATE THE IMAGE WITH THE DENDROGRAM
        create_dendrogram ($textcluster,$comp,$max,$_POST["method"],$_POST["len"]);

// SHOW DENDROGRAM
print "<img src=image.png?".date("U")." border=1>";

// SHOW TIME REQUIRED FOR COMPUTING
$timetotal=date("U")-$timestart;
print "<p>Computed in $timetotal seconds<br>";


// #######################################################################################
// ##############################       FUNCTIONS     ####################################
// #######################################################################################
function get_cases($a){
        $done="";
        foreach($a as $key => $val){
                $done.="#$key";
                foreach($a[$key] as $key2 =>$val2){
                        $done.="#$key2";
                }
        }
        $cases=preg_split("/#/",$done,-1,PREG_SPLIT_NO_EMPTY);
        $cases=array_unique($cases);
        sort($cases);
        return $cases;
}
//#######################################################################################
function new_array($a,$cases,$x,$y){
        $cases=get_cases($a);
        for($j=0; $j<sizeof($cases)+1;$j++){
                $key=$cases[$j];

                // next 3 lines are required in windows for correct comparison
                settype($key, "string");
                settype($x, "string");
                settype($y, "string");

                if ($key==$x or $key==$y){continue;}
                if ($a[$key][$x]!=""){
                        if ($a[$key][$y]!=""){$temp_a[$key]["($x,$y)"]=($a[$key][$x]+$a[$key][$y])/2;}
                        if ($a[$x][$key]!=""){$temp_a[$key]["($x,$y)"]=($a[$key][$x]+$a[$x][$key])/2;}
                        if ($a[$y][$key]!=""){$temp_a[$key]["($x,$y)"]=($a[$key][$x]+$a[$y][$key])/2;}

                }else{
                        if ($a[$key][$y]!=""){
                           if ($a[$x][$key]!=""){$temp_a[$key]["($x,$y)"]=($a[$key][$y]+$a[$x][$key])/2;}
                           if ($a[$y][$key]!=""){$temp_a[$key]["($x,$y)"]=($a[$key][$y]+$a[$y][$key])/2;}
                        }else{
                           if ($a[$y][$key]!=""){$temp_a[$key]["($x,$y)"]=($a[$y][$key]+$a[$y][$key])/2;}
                        }
                }

                for($i=$j+1; $i<sizeof($cases);$i++){
                        $key2=$cases[$i];
                        settype($key2, "string");
                        if ($key==$key2 or $key2==$x or $key2==$y){continue;}
                        if ($a[$key][$key2]!=""){$temp_a[$key][$key2]=$a[$key][$key2];}
                        if ($a[$key2][$key]!=""){$temp_a[$key][$key2]=$a[$key2][$key];}
                }
        }
        return $temp_a;
}

//#######################################################################################
function min_array($a){
        global $x, $y, $min;
        global $cases;                  // an array  for cases
        $str_cases="";
        $min=1000000;
        foreach($a as $key =>$val){
                $str_cases.="#$key";
                foreach($a[$key] as $key2 =>$val2){
                        if ($val==""){continue;}
                        $str_cases.="#$key2";
                        if ($val2<$min){
                                $min=$val2;
                                $x=$key;
                                $y=$key2;
                        }
                }
        }
        $cases=preg_split("/#/",$done,-1,PREG_SPLIT_NO_EMPTY);
        $cases=array_unique($cases);
        sort($cases);
        $min2=$min/2;
        return $min;
}
//#######################################################################################
function create_dendrogram($str,$comp,$max,$method,$len){

        $w=20;          //height for each line (case)

        $str=preg_replace("/\(|\)/","",$str).",";
        $a=preg_split("/,/",$str,-1,PREG_SPLIT_NO_EMPTY);
        $rows=sizeof($a);

        $width=600;     // width of scale from 0 to 2
        $im = @imagecreatetruecolor($width*1.2, $rows*$w+40) or die("Unable to start image. It is GD available?");
        $white =imagecolorallocate($im, 255, 255, 255);
        $black =imagecolorallocate($im, 0, 0, 0);
        $red =imagecolorallocate($im, 255, 0, 0);
        imagefilledrectangle($im,0,0,$width*1.2, $rows*$w+40,$white);

        $y=$rows*$w;    // vertical location
        $f=$width;      // multiplication factor

        // lines for scale
        $j=0.1;
        imageline ($im, log($j+1)*$f+20, $y, log($j+1)*$f+20, $y+10, $black);
        imagestring($im, 1, log($j+1)*$f-8+20, $y+12,  $j, $black);
        $j=0.2;
        imageline ($im, log($j+1)*$f+20, $y, log($j+1)*$f+20, $y+10, $black);
        imagestring($im, 1, log($j+1)*$f-8+20, $y+12,  $j, $black);
        $j=0.3;
        imageline ($im, log($j+1)*$f+20, $y, log($j+1)*$f+20, $y+10, $black);
        imagestring($im, 1, log($j+1)*$f-8+20, $y+12,  $j, $black);
        $j=0.5;
        imageline ($im, log($j+1)*$f+20, $y, log($j+1)*$f+20, $y+10, $black);
        imagestring($im, 1, log($j+1)*$f-8+20, $y+12,  $j, $black);
        $j=1.0;
        imageline ($im, log($j+1)*$f+20, $y, log($j+1)*$f+20, $y+10, $black);
        imagestring($im, 1, log($j+1)*$f-8+20, $y+12,  "1.0", $black);
        $j=1.5;
        imageline ($im, log($j+1)*$f+20, $y, log($j+1)*$f+20, $y+10, $black);
        imagestring($im, 1, log($j+1)*$f-8+20, $y+12,  $j, $black);
        $j=2.0;
        imageline ($im, log($j+1)*$f+20, $y, log($j+1)*$f+20, $y+10, $black);
        imagestring($im, 1, log($j+1)*$f-8+20, $y+12,  "2.0", $black);

        // write into the image the numbers corresponding to cases
        foreach($a as $n => $val){
                if (strlen($val)==1){$val=" $val";}
                imagestring($im,3, 5, $n*$w+5,  $val, $black);
        }

        // WRITE LINES
        foreach ($comp as $key => $val){
                $pos1=$pos2=0;
                foreach ($comp[$key] as $key2 => $val2){

                        // get position of case in the list
                        $keya=preg_replace("/\(|\)/","",$key);
                        $pos1=substr_count (" ,".substr ($str, 0,strpos(" ,".$str,",$keya,")),",")-0.4;
                        $keyb=preg_replace("/\(|\)/","",$key2);
                        $pos2=substr_count (" ,".substr ($str, 0,strpos(" ,".$str,",$keyb,")),",")-0.4;
                        if (substr_count($keya,",")>0){$pos1b=$pos1+substr_count($keya,",")/2;}else{$pos1b=$pos1;}
                        if (substr_count($keyb,",")>0){$pos2b=$pos2+substr_count($keyb,",")/2;}else{$pos2b=$pos2;}

                        // Position related data
                        $xkey1=$wherex[$key];
                        if ($xkey1==""){$xkey1=0;}
                        $xkey2=$wherex[$key2];
                        if ($xkey2==""){$xkey2=0;}
                        $max=max($xkey1,$xkey2);
                        $min=min($xkey1,$xkey2);
                        $xmax=$max+(($val2-($max))/2);
                        $val4=log($xmax+1)*$f;
                        $val4max=log($max+1)*$f;
                        $val4min=log($min+1)*$f;

                        // write lines
                        if ($wherex[$key]==$max){
                                imageline ($im, $val4max+20, $pos1b*$w, $val4+20, $pos1b*$w, $black);    // ---
                                imageline ($im, $val4+20, $pos1b*$w, $val4+20, $pos2b*$w, $black);       //   |
                                imageline ($im, $val4min+20, $pos2b*$w, $val4+20, $pos2b*$w, $black);    // ---
                        }else{
                                imageline ($im, $val4min+20, $pos1b*$w, $val4+20, $pos1b*$w, $black);    // ---
                                imageline ($im, $val4+20, $pos1b*$w, $val4+20, $pos2b*$w, $black);       //   |
                                imageline ($im, $val4max+20, $pos2b*$w, $val4+20, $pos2b*$w, $black);    // ---
                        }
                        $wherex["(".$key.",".$key2.")"]=$xmax;
                }

        }
        imageline ($im, $val4+20, ($pos1b+$pos2b)*$w/2, $val4+40, ($pos1b+$pos2b)*$w/2, $black);
        imageline ($im, 20, $y, $width*1.2, $y, $black);
        if ($method=="euclidean"){
                imagestring($im, 2, 5, $rows*$w+25,  "Euclidean distance for $len bases long oligonucleotides.", $red);
        }else{
                imagestring($im, 2, 5, $rows*$w+25,  "Pearson distance for z-scores of tetranucleotides.", $red);
        }
        imagestring($im, 2, $width*1, $rows*$w+25,  "by insilico.ehu.es", black);
        imagepng($im,"image.png");
        imagedestroy($im);
}

//#######################################################################################
function RevComp($code){
        $code=strrev($code);
        $code=str_replace("A", "t", $code);
        $code=str_replace("T", "a", $code);
        $code=str_replace("G", "c", $code);
        $code=str_replace("C", "g", $code);
        $code = strtoupper ($code);
        return $code;
}

//#######################################################################################
function Euclid_distance($a,$b,$k){
        // Wang et al, Gene 2005; 346:173-185
        $c=sqrt(pow(2,$k))/pow(4,$k);   // contant
        $sum=0;
        foreach($a as $key => $val){
                       $sum+= pow($val-$b[$key],2);
        }
        return $c*sqrt($sum);
}
//#######################################################################################
function Pearson_distance($vals_x,$vals_y){
        // normal correlation
        if (sizeof($vals_x)!= sizeof($vals_y)){return;}
        $sum_x=0;
        $sum_x2=0;
        $sum_y=0;
        $sum_y2=0;
        $sum_xy=0;
        $n=sizeof($vals_x);
        foreach($vals_x as $key => $val){
                $val_x=$val;
                $val_y=$vals_y[$key];
                $sum_x+=$val_x;
                $sum_x2+=$val_x*$val_x;
                $sum_y+=$val_y;
                $sum_y2+=$val_y*$val_y;
                $sum_xy+=$val_x*$val_y;
                //print "$val_x\t$val_y\n";
        }
        // calculate regression
        $regresion=($sum_xy-(1/$n)*$sum_x*$sum_y)/((sqrt($sum_x2-(1/$n)*$sum_x*$sum_x)*(sqrt($sum_y2-(1/$n)*$sum_y*$sum_y))));
        if ($regresion>0.999999999){$regresion=1;}      // round data
        return (1-$regresion);

}
//#######################################################################################
function compute_zscores_for_tetranucleotides($theseq){
        // as described by Teeling et al. BMC Bioinformatics 2004, 5:163.
        $theseq.=" ".RevComp($theseq);
        $i=0;
        $len=strlen($theseq)-2+1;
        while ($i<$len){
            $seq=substr($theseq,$i,2);
            $oligos2[$seq]++;
            $i++;
        }
        $i=0;
        $len=strlen($theseq)-3+1;
        while ($i<$len){
            $seq=substr($theseq,$i,3);
            $oligos3[$seq]++;
            $i++;
        }
        $i=0;
        $len=strlen($theseq)-4+1;
        while ($i<$len){
            $seq=substr($theseq,$i,4);
            $oligos4[$seq]++;
            $i++;
        }
        $base_a=array("A","C","G","T");
        $base_b=array("A","C","G","T");
        $base_c=array("A","C","G","T");
        $base_d=array("A","C","G","T");
        $base_e=array("A","C","G","T");
        $base_f=array("A","C","G","T");

        // COMPUTE Z-SCORES FOR TETRANUCLEOTIDES
        $i=0;
        foreach($base_a as $key_a => $val_a){
        foreach($base_b as $key_b => $val_b){
        foreach($base_c as $key_c => $val_c){
        foreach($base_d as $key_d => $val_d){
                $exp[$val_a.$val_b.$val_c.$val_d] = ($oligos3[$val_a.$val_b.$val_c]*$oligos3[$val_b.$val_c.$val_d])/$oligos2[$val_b.$val_c];
                $var[$val_a.$val_b.$val_c.$val_d] = $exp[$val_a.$val_b.$val_c.$val_d] *((($oligos2[$val_b.$val_c]-$oligos3[$val_a.$val_b.$val_c])*($oligos2[$val_b.$val_c]-$oligos3[$val_b.$val_c.$val_d]))/pow($oligos2[$val_b.$val_c],2));
                $zscore[$i] = ($oligos4[$val_a.$val_b.$val_c.$val_d]-$exp[$val_a.$val_b.$val_c.$val_d])/sqrt($var[$val_a.$val_b.$val_c.$val_d]);
                $i++;
        }}}}
        return $zscore;
}
//#######################################################################################
function oligo_frequencies_standar($cadena,$len_oligos){
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
        //para oligos de 2
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
        //para oligos de 3
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
        //para oligos de 4
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
                //para oligos de 5
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

        //para oligos de 6
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
        $oligos=standar_frecuencies($oligos, $len_oligos);
        return $oligos;
}

function standar_frecuencies($array, $m){
        $sum=0;
        foreach($array as $k => $v){
                $sum+=$v;
        }
        $c=pow(4,$m)/$sum;
        foreach($array as $k => $v){
                $array[$k]= $c*$v;
        }
        return $array;
}
// #######################################################################################
function print_form(){
?>
        <table cellpadding=10>
        <tr><td bgcolor=ddddff>

        <h1>Distance between sequences:<br>comparison of oligonucleotide composition</h1>

        <ul>
        <li>This tool will compute distance between input sequences and UPGMA clustering will be applied.</li>
        <li>Distances are shown in a table, and a dendrogram is displaied.</li>
        <li>Check carefully the results. Theirvalue for phylogenetics is limited.</li>
        </ul>

        <center>

        <form method="post" action="<? print $_SERVER["PHP_SELF"]; ?>">
        Copy your sequences bellow as Fasta (Maximum length: 2 MB)<br>
        <textarea name="seq" cols="80" rows="10"></textarea>
        </center>
        <p>Choose method to compute distances (<a href=http://insilico.ehu.es/oligoweb/info/distance.php>info</a>)
        <br>
        <input type=radio name=method value=pearson> Pearson distance for z-scores of tetranucleotides (>20000 bp sequences)&nbsp; &nbsp;
        <br>
        <input type=radio name=method value=euclidean checked>Euclidean distance for
        <select name=len>
        <option value=2>dinucleotides
        <option value=3>trinucleotides
        <option value=4 selected>tetranucleotides
        <option value=5>pentanucleotides
        <option value=6>hexanucleotides
        </select><p>

        <center>
        <p><input value="Compare sequences" type="submit">
        </form>
        </center>

        <div align=right><a href=http://insilico.ehu.es/oligoweb/my_distance/example.php>Example</a></div>
        </td></tr><tr><td>
        This is a simplified PHP script extracted from the online tool at <a href=http://insilico.ehu.es/oligoweb/>insilico.ehu.es/oligoweb/</a>.
        <br>Compare your sequence to up-to-date sequenced prokaryotes <a href=http://insilico.ehu.es/oligoweb/my_distance/>here</a>.
        <p>Source code is available at <a href=http://www.biophp.org/minitools/distance_among_sequences/>BioPHP.org</a>

        </td></tr></table>
        </center>
        </body>
        </html>
<?
}
?>
</body>
</html>
