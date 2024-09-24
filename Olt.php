<?php

class Olt
{
    protected $ipOlt;
    protected $username;
    protected $password;
    protected $telnet;
	
    function __construct($ip, $username, $password)
    {
        $this->ipOlt = $ip;
        $this->username = $username;
        $this->password = $password;
        $this->telnet = new Telnet($this->ipOlt, '23');
        $this->telnet->connect();
        $this->telnet->login($this->username, $this->password);
    }
    
    public function getOnuUnconfig()
    {
        $res = $this->telnet->exec('sho gpon onu un');
        if(preg_match('/No related information to show./', $res)) {
            return;
        }
        return $res;
    }
    
    public function getOnuState($interface)
    {
        $res = $this->telnet->exec('sho gpon onu state gpon-olt_'.$interface);
        preg_match_all('/(.*):(\d{1,})(.*)/', $res, $out);
        if(!$out[2])return ["index" => "", "message" => $res];
        
        foreach($out[2] as $x) {
            $data[$x] = $interface.":".$x;
        }
        return ["index" => $data, "status" => $out[3]];
    }
}
