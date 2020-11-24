<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;

class Hello extends Command
{
    protected $output;
    protected function configure()
    {
        // 指令配置
        $this->setName('hello')
            ->setDescription('the hello command');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->output = $output;

        $this->getAllFundBaseDetail();
        //$this->getFundList();
        //$this->getFundDetail(161725);
        /////$this->processList();
        //$this->getMinSet();

       /* $j = [
            "j1"=>[1,2,3,4],
            "j2"=>[2,3,4,5],
            "j3"=>[1,3,4,5],
            "j4"=>[1,2,6,7],
            "j5"=>[3,4,6,8],
            "j6"=>[2,3,5,6],
        ];

        $search = [2,3,4,1];

        var_dump($this->a($j,$search));exit('x');*/
        // 指令输出
        $output->writeln('hello');
    }

    function getFundList(){
        for ($i = 1; $i <= 400; $i++) {
            $page = $i;
            //$url = "https://fundmobapi.eastmoney.com/FundMNewApi/FundMNRankNewList?callback=jQuery3110662759194538407_1603873115906&fundtype=25&SortColumn=RZDF&Sort=desc&pageIndex=$page&pagesize=30&companyid=&deviceid=Wap&plat=Wap&product=EFund&version=2.0.0&Uid=&_=1603873115914";
            $url = "https://fundmobapi.eastmoney.com/FundMNewApi/FundMNRankNewList?callback=jQuery31109404841155024761_1603971874107&fundtype=0&SortColumn=RZDF&Sort=desc&pageIndex=$page&pagesize=30&companyid=&deviceid=Wap&plat=Wap&product=EFund&version=2.0.0&Uid=&_=1603971874109";
            $r = file_get_contents($url);
            $reg = '/.+\((\{.+\})\)/is';
            preg_match_all($reg,$r,$out);
            if(empty($out[1][0])){
                $this->output->writeln("空数据: $url ");
                continue;
            }
            $json = $out[1][0];

            $json = json_decode($json,true);
            if(empty($json["Datas"])){
                echo "url:结果为空 $url \n";

            }
            $this->processPage($json["Datas"]);
           //var_dump($json);

        }

    }


    function processPage($list){
        if(empty($list) || !is_array($list)){
            //var_dump($list);
            $this->output->writeln("list 是空");
            return;
        }
        foreach ($list as $k => $v) {
            $data =[];
            $data["code"] = $v["FCODE"];
            $data["name"] = $v["SHORTNAME"];
            $data["json"] = json_encode($v,JSON_UNESCAPED_UNICODE);
            Db::name("fund")->insert($data);
            //$this->getFundDetail($v);
        }

    }

    function processList(){

        $totalPage = ceil(Db::name("fund")->count()/100);
        for($i = 1; $i<=$totalPage; $i++){
            $r = Db::name("fund")->limit(100)->page($i)->select();
            foreach ($r as $k => $v) {
                $r = $this->getFundDetail($v);
                if($r){
                    Db::name("fund")->where("id",$v["id"])->update(["status"=>1]);
                }
                //usleep(500*1000);
            }
        }

    }

    //

    function getAllFundBaseDetail(){
        $totalPage = ceil(Db::name("fund")->count()/100);
        for($i = 1; $i<=$totalPage; $i++){
            $r = Db::name("fund")->limit(100)->page($i)->select();
            foreach ($r as $k => $v) {
                $r = $this->getFundBaseDetail($v);
                if($r){
                    Db::name("fund")->where("id",$v["id"])->update(["status"=>2]);
                }
                //usleep(500*1000);
            }
        }
    }
    function getFundBaseDetail($v){


        //$code = "006080";
        $code = $v["code"];
        $name = $v["name"];
        //$url = "https://fundmobapi.eastmoney.com/FundMNewApi/FundMNInverstPosition?callback=jQuery31109251527841978882_1603969297138&FCODE=$code&deviceid=Wap&plat=Wap&product=EFund&version=2.0.0&Uid=&DATE=2020-09-30&_=1603969297155";
        $url = "https://fundmobapi.eastmoney.com/FundMApi/FundBaseTypeInformation.ashx?callback=jQuery31108023436565071793_1605866377200&FCODE=$code&deviceid=Wap&plat=Wap&product=EFund&version=2.0.0&Uid=&_=1605866377202";
        $r = file_get_contents($url);
        $reg = '/.+\((\{.+\})\)/is';
        preg_match_all($reg,$r,$out);
        if(empty($out[1][0])){
            return;
        }
        $json = $out[1][0];
        $json = json_decode($json,true);
        $datas = $json["Datas"];

        $data = [];
        $data["y1"] = $datas["SYL_1N"];
        $data["m6"] = $datas["SYL_6Y"];
        $data["m1"] = $datas["SYL_Y"];
        $data["ftype"] = $datas["FTYPE"];
        $data["fundtype"] = $datas["FUNDTYPE"];
        $data["baseinfo_json"] = json_encode($datas);


        Db::name("fund")->where("code",$code)->update($data);
        return true;

    }


















    function getFundDetail($v){
        //$code = "006080";
        $code = $v["code"];
        $name = $v["name"];
        $url = "https://fundmobapi.eastmoney.com/FundMNewApi/FundMNInverstPosition?callback=jQuery31109251527841978882_1603969297138&FCODE=$code&deviceid=Wap&plat=Wap&product=EFund&version=2.0.0&Uid=&DATE=2020-09-30&_=1603969297155";
        $r = file_get_contents($url);
        $reg = '/.+\((.+)\)/is';
        preg_match_all($reg,$r,$out);
        if(empty($out[1][0])){
            return;
        }
        $json = $out[1][0];
        $json = json_decode($json,true);
        $datas = $json["Datas"];
        $stocks = $datas["fundStocks"];
        $fundboods = $datas["fundboods"];
        $fundfofs = $datas["fundfofs"];

        //print_r($stocks);
        /*print_r($fundboods);
        print_r($fundfofs);*/


        if(empty($stocks) || !is_array($stocks)) return;
        foreach ($stocks as $k=>$v){
            $data = [];
            $data["code"] = $v["GPDM"];
            $data["name"] = $v["GPJC"];
            $data["JZBL"] = $v["JZBL"];
            $data["PCTNVCHG"] = $v["PCTNVCHG"];
            $data["fund_code"] = $code;
            $data["fund_name"] = $name;
            $data["json"] = json_encode($v,JSON_UNESCAPED_UNICODE);
            DB::name("stock")->insert($data);


        }
        return true;
        //exit('x');

/*        "GPDM": "000568",
"GPJC": "",
"JZBL": "17.01",
"TEXCH": "2",
"ISINVISBL": "0",
"PCTNVCHG": "3.99",
"NEWTEXCH": "0"*/


    }







public $arr = [
    ["bj","sh","tj"],
    ["gz","bj","sz"],
    ["cd","sh","hz"],
    ["sh","tj"],
    ["hz","dl"],
];


    function getMinSet(){
        //$search = ["bj","sh","tj","hz","dl"];
        $search = ["603259","300676","002007","300529"]; //验证成功
        $search = [
            "600519",
            "000858",
            "002475",
            "601318",
            "000333",
            "601012",
           "600036",
            "600276",
            "300750",
            /*"600887",*/
        ];

        $keys = [];
        foreach ($search as $k=>$v){
            $keys[] = $this->query($v);
        }

        /*$result = array_intersect($keys["bj"],$keys["sh"]);
        $r2 = array_intersect($result,$keys["tj"]);
        var_dump($r2);exit('x');*/
        //$this->repeateIntersect([$keys["603259"],$keys["300676"],$keys["002007"],$keys["300529"]]);
        //$this->repeateIntersect([$keys["600519"],$keys["000858"]]);
        $this->repeateIntersect($keys);
            //$keys["002475"],$keys["601318"],$keys["000333"],$keys["601012"],$keys["600036"],$keys["600276"],$keys["300750"],$keys["600887"]]);


    }

    function repeateIntersect($arr){
        $count = count($arr);
        for($i = 0; $i<$count-1; $i++){

            $arr[$i+1] = array_intersect($arr[$i],$arr[$i+1]);
        }
        var_dump($arr[count($arr)-1]); //最后一个元素放的就是最终结果
        return $arr[count($arr)-1];

    }

    function query2($search){
        $rowIDs = [];
        foreach ($this->arr as $k=>$v){
            if(in_array($search,$v)){
                $rowIDs[] = $k;
            }
        }
        return $rowIDs;
    }

    function query($search){
        //$rowIDs = [];
        $r = Db::name("stock")->where("code",$search)->select()->toArray();
        $fund_codes = array_column($r,"fund_code");
        return $fund_codes;
    }

































}
