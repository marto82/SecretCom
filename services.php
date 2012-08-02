<?php

require_once 'Slim/Slim.php';


$app = new Slim();

$app->post('/create-channel', 'createChannel');

$app->get('/list-channels', 'getListChannels');

$app->post('/open-channel', 'openChannel');

$app->post('/delete-channel', 'deleteChannel');

$app->post('/send-message', 'sendMessage');

$app->post('/send-url', 'sendUrl');

$app->post('/send-file', 'sendFile');

function createChannel() {
    require_once 'bootstrap.php';

    try {
        Slim::getInstance()->contentType('application/json');
        $request = Slim::getInstance()->request();
        $body = json_decode($request->getBody());
        $channelName = addslashes(trim($body->channelName));
        $password = trim($body->password);
        if (filterName($channelName) && strlen($channelName) > 3) {

            if (strlen($password) > 3) {
                if (!is_channel_exists($channelName)) {

                    $collection = getConnection();
                    $collection->insert(array('channelName' => $channelName, 'password' => md5($password), array()));

                    $dropbox->create($channelName);
                    echo json_encode(array('emptyMessage' => array('empty' => 'Channel is empty!')));
                } else {
                    header('HTTP/1.1 500 Internal Server Booboo');
                    header('Content-Type: application/json');
                    die(json_encode(array('error' => 'Channel name exists!')));
                }
            } else {
                header('HTTP/1.1 500 Internal Server Booboo');
                header('Content-Type: application/json');
                die(json_encode(array('error' => 'Password must be longer then 3 characters!')));
            }
        } else {
            header('HTTP/1.1 500 Internal Server Booboo');
            header('Content-Type: application/json');
            die(json_encode(array('error' => 'The username must contain Latin <br />characters(a - z) and / or numbers (0-9).')));
        }
    } catch (MongoConnectionException $e) {
        header('HTTP/1.1 500 Internal Server Booboo');
        header('Content-Type: application/json');
        die(json_encode(array('error' => 'Couldn\'t connect to mongodb, is the "mongo" process running?')));
    }
}

function getListChannels() {
    try {
        $app = Slim::getInstance();
        $app->contentType('application/json');
        $collection = getConnection();
        $cursor = $collection->find();
        if ($cursor->count() > 0) {
            $arr = array();
            foreach ($cursor as $doc) {
                $arr[] = $doc['channelName'];
            }

            echo json_encode(array_unique($arr));
        } else {
            echo json_encode(array('message' => 'No channels!Please create channel!'));
        }
    } catch (MongoConnectionException $e) {
        header('HTTP/1.1 500 Internal Server Booboo');
        header('Content-Type: application/json');
        die(json_encode(array('error' => 'Couldn\'t connect to mongodb, is the "mongo" process running?')));
    }
}

function openChannel() {
    require_once 'aes.class.php';
    
    $app = Slim::getInstance();
    $app->contentType('application/json');
    $request = $app->request();
    $body = json_decode($request->getBody());
    $channelName = trim($body->channelName);
    $password = trim($body->password);
    try {
        $collection = getConnection();
        $result = $collection->find(array('channelName' => $channelName, 'password' => md5($password)), array("safe" => true));
        $count = $result->count();
        if ($count > 0) {

            $result = $collection->find(array('channelName' => $channelName));

            $arr_result = array();
            if ($result->count() > 1) {
                $array = iterator_to_array($result);
                foreach ($array as $arr) {
                    if (count($arr['0']) != 0) {
                        $arr_result[] = $arr['0'];
                    }
                }
                $arr_res = array();
                foreach ($arr_result as $arr) {
                    if (array_key_exists('message', $arr)) {
                        $arr_res[]['message'] = AesCtr::decrypt($arr['message'], $password, 256);
                    }
                    if(array_key_exists('url', $arr)) {
                        $arr_res[]['url'] = AesCtr::decrypt($arr['url'], $password, 256);
                    }
                    if(array_key_exists('description', $arr)) {
                        $arr_res[]['description'] = AesCtr::decrypt($arr['description'], $password, 256);
                    }
                    if(array_key_exists('file_name', $arr)) {
                        $arr_res[]['file_name'] = AesCtr::decrypt($arr['file_name'], $password, 256);
                    }
                    if(array_key_exists('url_to_file', $arr)) {
                        $arr_res[]['url_to_file'] = AesCtr::decrypt($arr['url_to_file'], $password, 256);
                    }
                    if(array_key_exists('mime_type', $arr)) {
                        $arr_res[]['mime_type'] = AesCtr::decrypt($arr['mime_type'], $password, 256);
                    }
                }
                echo json_encode($arr_res);
            } else {
                echo json_encode(array('emptyMessage' => array('empty' => 'Channel is empty!')));
            }
        } else {
            header('HTTP/1.1 500 Internal Server Booboo');
            header('Content-Type: application/json');
            die(json_encode(array('error' => 'Invalid channel name or password')));
        }
    } catch (MongoCursorException $e) {
        header('HTTP/1.1 500 Internal Server Booboo');
        header('Content-Type: application/json');
        die(json_encode(array('error' => 'Error handling database! Try it again!')));
    } catch (MongoConnectionException $e) {
        header('HTTP/1.1 500 Internal Server Booboo');
        header('Content-Type: application/json');
        die(json_encode(array('error' => 'Couldn\'t connect to mongodb, is the "mongo" process running?')));
    }
}

function deleteChannel() {
    require_once 'bootstrap.php';
    $app = Slim::getInstance();
    $app->contentType('application/json');
    $request = $app->request();
    $body = json_decode($request->getBody());
    $channelName = trim($body->channelName);
    $password = trim($body->password);

    try {
        $collection = getConnection();
        $result = $collection->find(array('channelName' => $channelName, 'password' => md5($password)), array("safe" => true));
        $count = $result->count();
        if ($count > 0) {
            $collection->remove(array('channelName' => $channelName), array('safe' => true));
            $put = $dropbox->delete($channelName);

            echo json_encode(array('success' => 'Channel ' . $channelName . 'was deleted!'));
        } else {
            header('HTTP/1.1 500 Internal Server Booboo');
            header('Content-Type: application/json');
            die(json_encode(array('error' => 'Invalid channel name or password')));
        }
    } catch (MongoCursorException $e) {
        header('HTTP/1.1 500 Internal Server Booboo');
        header('Content-Type: application/json');
        die(json_encode(array('error' => 'Error deleting channel!Please try again!')));
    } catch (MongoConnectionException $e) {
        header('HTTP/1.1 500 Internal Server Booboo');
        header('Content-Type: application/json');
        die(json_encode(array('error' => 'Couldn\'t connect to mongodb, is the "mongo" process running?')));
    }
}

function sendMessage() {
    require_once 'aes.class.php';

    $app = Slim::getInstance();
    $app->contentType('application/json');
    $request = $app->request();
    $body = json_decode($request->getBody());
    $channelName = trim($body->channelName);
    $password = trim($body->password);
    $message = addslashes(trim($body->message));
    if (strlen($message) > 0) {
        try {
            $collection = getConnection();
            $result = $collection->find(array('channelName' => $channelName, 'password' => md5($password)), array("safe" => true));
            $count = $result->count();
            if ($count > 0) {
                $enc_msg = AesCtr::encrypt($message, $password, 256);
                $collection->insert(array('channelName' => $channelName, 'password' => md5($password), array('message' => $enc_msg)));
            } else {
                header('HTTP/1.1 500 Internal Server Booboo');
                header('Content-Type: application/json');
                die(json_encode(array('error' => 'Invalid channel name or password')));
            }
        } catch (MongoCursorException $e) {
            header('HTTP/1.1 500 Internal Server Booboo');
            header('Content-Type: application/json');
            die(json_encode(array('error' => 'Error handling database! Try it again!')));
        } catch (MongoConnectionException $e) {
            header('HTTP/1.1 500 Internal Server Booboo');
            header('Content-Type: application/json');
            die(json_encode(array('error' => 'Couldn\'t connect to mongodb, is the "mongo" process running?')));
        }
    } else {
        header('HTTP/1.1 500 Internal Server Booboo');
        header('Content-Type: application/json');
        die(json_encode(array('error' => 'Empty message!Please enter some text!')));
    }
}

function sendUrl() {
    require_once 'aes.class.php';
    $app = Slim::getInstance();
    $app->contentType('application/json');
    $request = $app->request();
    $body = json_decode($request->getBody());
    $channelName = addslashes(trim($body->channelName));
    $password = trim($body->password);
    $url = $body->url;
    $description = addslashes(trim($body->description));
    if (validateURL($url)) {
        try {
            $collection = getConnection();
            $result = $collection->find(array('channelName' => $channelName, 'password' => md5($password)), array("safe" => true));
            $count = $result->count();
            if ($count > 0) {
                $enc_url = AesCtr::encrypt($url, $password, 256);
                $enc_desc = AesCtr::encrypt($description, $password, 256);
                $collection->insert(array('channelName' => $body->channelName, 'password' => md5($body->password), array('url' => $enc_url, 'description' => $enc_desc)));
            } else {
                header('HTTP/1.1 500 Internal Server Booboo');
                header('Content-Type: application/json');
                die(json_encode(array('error' => 'Invalid channel name or password')));
            }
        } catch (MongoConnectionException $e) {
            header('HTTP/1.1 500 Internal Server Booboo');
            header('Content-Type: application/json');
            die(json_encode(array('error' => 'Error handling database! Try it again!')));
        } catch (MongoConnectionException $e) {
            header('HTTP/1.1 500 Internal Server Booboo');
            header('Content-Type: application/json');
            die(json_encode(array('error' => 'Couldn\'t connect to mongodb, is the "mongo" process running?')));
        }
    } else {
        header('HTTP/1.1 500 Internal Server Booboo');
        header('Content-Type: application/json');
        die(json_encode(array('error' => 'Invalid url address')));
    }
}

function sendFile() {
    require_once 'bootstrap.php';
    require_once 'aes.class.php';

    try {
        $channelName = addslashes(trim($_POST['channelName']));
        $password = trim($_POST['password']);
        $collection = getConnection();
        $result = $collection->find(array('channelName' => $channelName, 'password' => md5($password)), array("safe" => true));
        $count = $result->count();
        if ($count > 0) {
            if ($_FILES["file"]['tmp_name']) {
                $filename = $_FILES['file']['name'];
                $file_type = $_FILES["file"]["type"];
                $file_size = $_FILES['file']['size'];
                if ($file_size > 2097152) {
                    header('HTTP/1.1 500 Internal Server Booboo');
                    header('Content-Type: application/json');
                    unlink($_FILES["file"]["tmp_name"]);
                    die(json_encode(array('error' => 'File can not be greater than 2 MB')));
                } else {
                    //$ext = substr($filename, strpos($filename, '.'), strlen($filename) - 1); // Get the extension from the filename.
                    
                    $put = $dropbox->putFile($_FILES["file"]["tmp_name"], $filename, $channelName);

                    $share = $dropbox->shares($channelName . '/' . $filename);

                    $arr = array();

                    foreach ($share as $data) {
                        $arr[] = $data;
                    }

                    $enc_filename = AesCtr::encrypt($filename, $password, 256);
                    $enc_url_to_file = AesCtr::encrypt($arr[1]->url, $password, 256);
                    $enc_file_type = AesCtr::encrypt($file_type, $password, 256);
                    $collection->insert(array('channelName' => $channelName, 'password' => md5($password), array('file_name' => $enc_filename, 'url_to_file' => $enc_url_to_file, 'mime_type' => $enc_file_type)));
                    unlink($_FILES["file"]["tmp_name"]);
                }
            } else {
                header('HTTP/1.1 500 Internal Server Booboo');
                header('Content-Type: application/json');
                die(json_encode(array('error' => 'No files uploaded!')));
            }
        } else {
            header('HTTP/1.1 500 Internal Server Booboo');
            header('Content-Type: application/json');
            die(json_encode(array('error' => 'Invalid channel name or password')));
        }
    } catch (MongoConnectionException $e) {
        header('HTTP/1.1 500 Internal Server Booboo');
        header('Content-Type: application/json');
        die(json_encode(array('error' => 'Error handling database! Try it again!')));
    } catch (MongoConnectionException $e) {
        header('HTTP/1.1 500 Internal Server Booboo');
        header('Content-Type: application/json');
        die(json_encode(array('error' => 'Couldn\'t connect to mongodb, is the "mongo" process running?')));
    }
}

$app->run();

function getConnection() {
    $m = new Mongo('mongodb://2ae4111f:bga824gass8gdtlec8o09a6ra1@ds035607-a.mongolab.com:35607/orchestra_2ae4111f_70fd7'); // connect
    $db = $m->orchestra_2ae4111f_70fd7;
    $collection = $db->posts;

    return $collection;
}

function is_channel_exists($channelName) {
    try {
        $collection = getConnection();
        $search = array('channelName' => $channelName);
        $cursor = $collection->find($search, array("safe" => true));
        if ($cursor->count() > 0) {
            return true;
        } else {
            return false;
        }
    } catch (MongoConnectionException $e) {
        return false;
    }
}

function validateURL($url) {
    $pattern = '/^(([\w]+:)?\/\/)?(([\d\w]|%[a-fA-f\d]{2,2})+(:([\d\w]|%[a-fA-f\d]{2,2})+)?@)?([\d\w][-\d\w]{0,253}[\d\w]\.)+[\w]{2,4}(:[\d]+)?(\/([-+_~.\d\w]|%[a-fA-f\d]{2,2})*)*(\?(&amp;?([-+_~.\d\w]|%[a-fA-f\d]{2,2})=?)*)?(#([-+_~.\d\w]|%[a-fA-f\d]{2,2})*)?$/';
    if (preg_match($pattern, $url)) {
        return true;
    } else {
        return false;
    }
}

function filterName($name, $filter = "[^a-zA-Z0-9\-\_\.]") {
    return preg_match("~" . $filter . "~iU", $name) ? false : true;
}

?>