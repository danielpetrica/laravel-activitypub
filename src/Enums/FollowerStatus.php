<?php

namespace DanielPetrica\LaravelActivityPub\Enums;

enum FollowerStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
}
