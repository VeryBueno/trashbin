<?php

namespace Igorw\Trashbin;

interface Storage
{
    public function get($id);
    public function set($id, array $value);
    public function all();
    public function delete($id);
}
