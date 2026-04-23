<?php

namespace App\Services\Orion\Contracts;

interface AiProvider
{
    public function name(): string;

    public function isConfigured(): bool;

    /**
     * @param  array{system?:string,max_tokens?:int,temperature?:float}  $options
     * @return array{content:string,model:string,input_tokens:?int,output_tokens:?int}
     */
    public function complete(string $prompt, array $options = []): array;
}
