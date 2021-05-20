/*
 *  Copyright (c) 2017-2021, Arm Limited, All Rights Reserved
 *  SPDX-License-Identifier: Apache-2.0
 *
 *  Licensed under the Apache License, Version 2.0 (the "License"); you may
 *  not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 *  WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 *
 *  This file is part of Mbed TLS (https://www.trustedfirmware.org/projects/mbed-tls/)
 */

<?php
# Global configuration
#
$includes='../web_framework/includes/';
$site_includes='../includes/';

error_reporting(E_ALL | E_STRICT);
ini_set("display_errors", 1);

require_once($includes.'wf_core.inc.php');

require_once($includes."config_values.inc.php");
require_once($site_includes."forum_base.inc.php");
require_once($site_includes."text.inc.php");
require_once($site_includes."php-markdown/markdown.php");

$forum_config = new ConfigValues($global_database, 'forum');
$forum = new ForumBase($global_info, $forum_config);

// Gather posts that require notifications
//
$posts = $forum->get_posts_for_notification();

// Gather users that want notifications
//
$users = $forum->get_users_for_notification();
$notifications = false;
foreach ($posts as $post)
{
    $user_config = new UserConfigValues($global_database, $post->user_id, 'forum');
    $automatic_send = $user_config->get_value('automatic_send', '0');

    if ($automatic_send === '1')
    {
        printf('Automatically sending '.$post->get_topic()->title.' by '.$post->get_user()->username."\n");
        $post->update_field('activity_mailed', POST_READY);
        continue;
    }

    printf('Notifying for post in '.$post->get_topic()->title.' by '.$post->get_user()->username."\n");
    $forum->notify_new_post_by_mail($post, $users);
    $notifications = true;
}

// Gather posts in queue
//
$posts = $forum->get_posts_in_queue();
foreach ($posts as $post)
{
    $user_config = new UserConfigValues($global_database, $post->user_id, 'forum');
    $automatic_send = $user_config->get_value('automatic_send', '0');

    if ($automatic_send === '1')
    {
        printf('Automatically sending '.$post->get_topic()->title.' by '.$post->get_user()->username."\n");
        $post->update_field('activity_mailed', POST_READY);
        continue;
    }

    if ($notifications !== true)
        continue;

    printf('Queued post in '.$post->get_topic()->title.' by '.$post->get_user()->username.PHP_EOL);
    printf($global_config['http_mode'].'://'.$global_config['server_name'].'/discussions/'.$post->get_topic()->full_link_name.PHP_EOL);
}

// Gather posts that require sending
//
$posts = $forum->get_posts_for_sending();

// Gather users that want activity sent
//
$users = $forum->get_users_for_sending();

foreach ($posts as $post)
{
    printf('Sending activity for post in '.$post->get_topic()->title.' by '.$post->get_user()->username.PHP_EOL);
    $forum->send_new_post_by_mail($post, $users);
}
?>
