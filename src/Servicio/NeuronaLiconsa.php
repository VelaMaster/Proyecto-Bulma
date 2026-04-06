<?php
class NeuronaLiconsa {
    private $pesos;
    private $bias;

    public function __construct($pesos, $bias = 0) {
        $this->pesos = $pesos;
        $this->bias = $bias;
    }

    public function predecir($entradas) {
        $sumaPonderada = $this->bias;
        for ($i = 0; $i < count($entradas); $i++) {
            $sumaPonderada += ($entradas[$i] * ($this->pesos[$i] ?? 0));
        }
        return max(0, ceil($sumaPonderada));
    }
}