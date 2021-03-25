<?php

namespace Photofancy\OrderIt\Helper;

class StatusHelper
{
    const TYPE_CG_PERSONALISED_CG           = 2;
    const TYPE_CG_STATIC_CG                 = 5;

    const TYPE_PF_PERSONALISED_CG           = 'personalised_cg';
    const TYPE_PF_STATIC_CG                 = 'static_cg';

    const STATE_ERROR_CUSTOM_GATEWAY        = 'error_cg';
    const STATE_EXPORT_FLIP                 = 'export_flip';

    const STATUS_ORDER_IT_RESPONSE_RECEIVED = 'Received';

    const MQ_ORDER_IT_CREATE                = 'order.custom_gateway.create';

}
