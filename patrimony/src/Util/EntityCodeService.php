<?php

namespace App\Util;

use Symfony\Component\String\Slugger\AsciiSlugger;

class EntityCodeService {
    /**
     * Function to generate code for director.
     *
     * @return string
     *   Generated code for director.
     */
    public function computeCode(string $name): string {
        $slugger = new AsciiSlugger();
        $slug = $slugger->slug($name, '_')->toString();
        $normalized = strtoupper($slug);;

        return str_replace(' ', '_', $normalized);
    }
}