<?php

namespace fritzco\base;

use fritzco\interfaces\INumber;

class BaseNumber implements INumber
{
    private $type;
    private $other_type;
    private $number;
    
    public function setType($type=0, $other_type=NULL){
        if($type<0 || $type>NumberType::_max){
            throw new \InvalidArgumentException('no valid number type, please use ENUM NumberType to specify type (e.g. NumberType::HOME)');
        }
        if($type==NumberType::OTHER){
            $this->other_type = $other_type;
        }
        $this->type = $type;
    }
    public function getType(){
        return $this->type;
    }
    public function getOtherType(){
        return $this->other_type;
    }
    public function getDisplayableType(){
    	if($this->type==NumberType::OTHER){
    		return $this->other_type;
    	}
    	else{
        	return NumberType::string($this->type);
        }
    }
    public function setNumber($number){
        $this->number = $number;
    }
    
    public function getNumber(){
        return $this->number;
    }
}

?>
