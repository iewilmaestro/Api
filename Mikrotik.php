<?php

class MikrotikModel
{
	protected $ipMikrotik;
	protected $username;
	protected $password;
	protected $apimikrotik;
	
	function __construct($ip, $port, $user, $password)
	{
		$this->ipMikrotik = $ip;
		$this->port = $port;
		$this->username = $user;
		$this->password = $password;
		$this->apimikrotik = new RouterosAPI();
		$this->apimikrotik->debug = true;
		$this->apimikrotik->connect($this->ipMikrotik, $this->port, $this->username, $this->password);
	}
	public function GetQueueSimple()
	{
		$this->apimikrotik->write('/queue/simple/print');
		$read = $this->apimikrotik->read(false);
		$array = $this->apimikrotik->parseResponse($read);
		return $array;
	}
	public function GetQueueTree()
	{
		$this->apimikrotik->write('/queue/tree/print');
		$read = $this->apimikrotik->read(false);
		$array = $this->apimikrotik->parseResponse($read);
		return $array;
	}
	public function getDataWireless($id)
	{
		// jika simple que;
		$list = $this->GetQueueSimple();
		foreach($list as $cek){
			$id_pel = explode('-',$cek['name'])[0];
			$nama_pel = explode('-',$cek['name'])[1];
			$ip_pel = explode('/',$cek['target'])[0];
			if($id == $id_pel){
				return [
				"status" => 1,
				"nama" => $nama_pel,
				"ip" => $ip_pel
				];
			}
		}
		
		//jika que tree R-TA
		$list = $this->GetQueueTree();
		foreach($list as $cek){
			$id_pel = explode('-',$cek['name'])[0];
			$nama_pel = explode('-',$cek['name'])[1];
			$ip_pel = $cek['packet-mark'];
			if($id == $id_pel){
				return [
				"status" => 1,
				"nama" => $nama_pel,
				"ip" => $ip_pel
				];
			}
		}
		return [
		"status" => 0,
		"msg" => "data tidak di temukan"
		];
	}
	public function IsolirWireless($address, $id, $nama)
	{
		return $this->apimikrotik->comm("/ip/firewall/address-list/add",
			[
			"address" => $address,
			"list" => "ISOLIR",
			"comment" => "ISOLIR $id-$nama"
			]
		);
	}
	public function UnisolirWIreless($address)
	{
		$this->apimikrotik->write('/ip/firewall/address-list/print');
		$addresslist = $this->apimikrotik->read();
		foreach($addresslist as $isolir){
			if($address == $isolir['address']){
				$this->apimikrotik->write("/ip/firewall/address-list/remove",false);
				$this->apimikrotik->write("=.id=".$isolir['.id']);
				return $this->apimikrotik->read(false);
			}
		}
		return [0=>"!nodata"];
	}
	
	public function IsolirGpon($id, $paket)
	{
		//change set
		$this->apimikrotik->comm("/ppp/secret/set",
			[
			"profile" => "ISOLIR-".$paket,
			"numbers" => $id,
			]
		);
		//remove active con
		$this->apimikrotik->write('/ppp/active/print');
		$active = $this->apimikrotik->read();
		foreach($active as $on){
			if($on['name'] == $id){
				$this->apimikrotik->write("/ppp/active/remove",false);
				$this->apimikrotik->write("=.id=".$on['.id']);
				$read = $this->apimikrotik->read(false);
				return json_encode($read);
			}
		}
	}
	
	public function UnisolirGpon($id, $paket)
	{
		$this->apimikrotik->comm("/ppp/secret/set",
			[
			"profile" => $paket,
			"numbers" => $id,
			]
		);
		$this->apimikrotik->write('/ppp/active/print');
		$active = $this->apimikrotik->read();
		foreach($active as $on){
			if($on['name'] == $id){
				$this->apimikrotik->write("/ppp/active/remove",false);
				$this->apimikrotik->write("=.id=".$on['.id']);
				return json_encode($this->apimikrotik->read(false));
			}
		}
	}	
}
