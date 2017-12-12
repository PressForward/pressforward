<?php

namespace GenderEngine\Matchers\Traits;

trait NameList
{
    private $maleList = [];

    private $femaleList = [];

    public function setMaleNameList(array $newMaleList)
    {
        $this->maleList = $newMaleList;
    }

    public function getMaleNameList()
    {
        return $this->maleList;
    }

    public function setFemaleNameList(array $newFemaleList)
    {
        $this->femaleList = $newFemaleList;
    }

    public function getFemaleNameList()
    {
        return $this->femaleList;
    }

    public function setNameLists(array $newMaleList, array $newFemaleList)
    {
        $this->setMaleNameList($newMaleList);
        $this->setFemaleNameList($newFemaleList);
    }
}

