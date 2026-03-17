<?php
namespace Modules\Contracts\AgentNetwork\Enums;
enum AgentStatus: string {
    case Active     = 'active';
    case Suspended  = 'suspended';
    case Terminated = 'terminated';
}
