<?php

namespace DanielPetrica\LaravelActivityPub\Enums;

enum ActivityObjectType: string
{
    case Article = 'Article';
    case Note = 'Note';
    case Image = 'Image';
    case Video = 'Video';
    case Document = 'Document';
    case Page = 'Page';
    case Event = 'Event';
    case Profile = 'Profile';
}
