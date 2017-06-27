<?php


namespace liw\service;


class Main
{
    private $string;

    public function run(){

        echo 'Enter your command: ';
        $command = trim(fgets(STDIN));

        if ($command == "parse"){
            $this->string .= "Путь к картинке; Адресс страницы" . "\n";
                echo 'Vvedite url adress: ';
                $url = trim(fgets(STDIN));
                $parser = new Parser();
                $parser->parse($url, $endUrl = '/');

                foreach ($parser->getArrayImage() as $k => $v){
                    $arr = $v->getImageUrl();
                    foreach ($arr as $item){
                        $this->string .= $item . "; " . $k . "\n";
                    }
                }
                $fp = fopen('file.csv', 'w');
                array_push($arr, $this->string);
                fputcsv($fp, $arr);
                fclose($fp);
            $this->run();
        }
        if ($command == "report"){
            if ($this->string != ""){
                echo $this->string;
            } else {
                $this->run();
            }
        }

        if ($command == "help"){
            $string = "To start the application, enter the command: parse" . "\n";
            $string .= "After command parse, enter url and press enter" . "\n";
            $string .= "To display the result, enter the command report and press enter" . "\n";
            echo $string;
            $this->run();
        }



    }
}