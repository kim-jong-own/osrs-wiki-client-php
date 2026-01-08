<?php

declare(strict_types=1);

namespace KimJongOwn\OsrsWiki\Factories;

use KimJongOwn\OsrsWiki\Model\Recipe;
use KimJongOwn\OsrsWiki\Model\RecipeMaterial;
use KimJongOwn\OsrsWiki\Model\RecipeProduct;
use KimJongOwn\OsrsWiki\Model\RecipeSkill;
use KimJongOwn\OsrsWiki\Model\SkillRequirement;

class RecipeFactory
{
    public function fromArray(array $productionData): Recipe
    {
        $product = new RecipeProduct(
            item: $productionData['output']['name'],
            quantity: (float)$productionData['output']['quantity'],
            subname: $productionData['output']['subtxt'] ?? null,
            note: $productionData['output']['quantitynote'] ?? null,
        );
        $materials = array_map(function (array $materialData) {
            return new RecipeMaterial(
                item: $materialData['name'],
                quantity: (int)$materialData['quantity'],
            );
        }, $productionData['materials'] ?? []);
        $skills = array_map(function (array $skillData) {
            return new RecipeSkill(
                requirement: new SkillRequirement(
                    skill: $skillData['name'],
                    level: (int)$skillData['level'],
                ),
                experience: (float)$skillData['experience'],
                boostable: isset($skillData['boostable']) && $skillData['boostable'] === 'Yes',
            );
        }, $productionData['skills'] ?? []);

        return new Recipe(
            product: $product,
            materials: $materials,
            skills: $skills,
            tools: isset($productionData['tools']) ? explode(', ', $productionData['tools']) : [],
            facilities: isset($productionData['facilities']) ? explode(', ', $productionData['facilities']) : [],
            ticks: isset($productionData['ticks']) ? (int)$productionData['ticks'] : null,
            members: (bool)$productionData['members'],
        );
    }
}
