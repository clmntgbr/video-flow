<?php

namespace App\Dto;

use App\Validator\Constraints\UniqueEmail;
use Symfony\Component\Validator\Constraints as Assert;

class UserRegister
{
    #[Assert\Email()]
    #[Assert\NotBlank()]
    #[UniqueEmail()]
    #[Assert\Type('string')]
    public ?string $email;

    #[Assert\NotBlank()]
    #[Assert\Length(min: 6)]
    #[Assert\Type('string')]
    public ?string $password;
}
