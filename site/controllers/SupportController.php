<?php

namespace Thrust\Controllers;

use Thrust\Models\EntTopics;
use Thrust\Models\EntArticles;

/**
 * Thrust\Controllers\SupportController.
 */
class SupportController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setTemplateBefore('private');
    }

    public function indexAction()
    {
        $identity = $this->auth->getIdentity();

        $topics = EntTopics::find([
            'conditions' => 'is_deleted = 0',
            'cache' => 30,
        ]);

        $typeTopics = [
            'active'    => [],
            'hidden'    => [],
        ];

        foreach ($topics as $topic) {
            $type = $topic->is_active ? 'active' : 'hidden';

            $topic->articles = $topic->getArticles([
                'conditions' => 'is_active = 1 AND is_deleted = 0',
                'cache' => 60,
            ]);

            $typeTopics[$type][] = $topic;
        }

        $data = [
            'topics'    => $typeTopics,
            'uploadUrl' => $this->config->application->uploadUrl,
        ];

        $this->view->setVars($data);
    }

    public function viewTopicAction()
    {
        $topic_id = $this->dispatcher->getParam('topic');

        $topic = EntTopics::findFirst([
            'conditions' => 'pk_id = ?1 AND is_active = 1 AND is_deleted = 0',
            'bind'       => [
                1        => $topic_id,
            ],
            'cache' => 30,
        ]);

        $articles = $topic->getArticles([
            'cache' => 30,
        ]);

        if (! $topic) {
            $this->flashSession->error('No topic found');
            return $this->response->redirect('support');
        }

        $typeArticles = [
            'active'    => [],
            'hidden'    => [],
        ];

        foreach ($articles as $article) {
            $type = $article->is_active ? 'active' : 'hidden';

            $typeArticles[$type][] = $article;
        }

        $data = [
            'topic'     => $topic,
            'articles'  => $typeArticles,
        ];

        $this->view->setVars($data);
    }

    public function viewArticleAction()
    {
        $article_id = $this->dispatcher->getParam('article');

        $article = EntArticles::findFirst([
            'conditions' => 'pk_id = ?1 AND is_active = 1 AND is_deleted = 0',
            'bind'       => [
                1        => $article_id,
            ],
            'cache' => 30,
        ]);

        if (! $article) {
            $this->flashSession->error('No article found');
            return $this->response->redirect('support');
        }

        $data = [
            'article'     => $article,
        ];

        $this->view->setVars($data);
    }

    public function editArticleAction()
    {
        $identity = $this->auth->getIdentity();

        $article_id = $this->dispatcher->getParam('article');

        if ($identity['orgId'] != 1) {
            throw new \Exception('Security Breach - User ID ' . $identity['id'] . ' tried to ' . ($article_id ? 'edit article ID ' . $article_id : 'create a new article'));
        }

        if ($article_id) {
            // edit article

            $article = EntArticles::findFirst([
                'conditions' => 'pk_id = ?1 AND is_active = 1 AND is_deleted = 0',
                'bind'       => [
                    1        => $article_id,
                ],
                'cache' => false,
            ]);

            if (! $article) {
                $this->flashSession->error('No article found');
                return $this->response->redirect('support');
            }

            $data = [
                'identifier'    => $article_id,
                'type'          => 'edit',
                'article'       => $article,
            ];
        } else {
            // new article

            $topic_id = $this->dispatcher->getParam('topic');

            $topic = EntTopics::findFirst([
                'conditions' => 'pk_id = ?1 AND is_active = 1 AND is_deleted = 0',
                'bind'       => [
                    1        => $topic_id,
                ],
                'cache' => 30,
            ]);

            if (! $topic) {
                $this->flashSession->error('No topic found');
                return $this->response->redirect('support');
            }

            $data = [
                'identifier'    => $topic_id,
                'type'          => 'new',
            ];
        }

        $this->view->setVars($data);
    }

    /**
     * ajax handler
     */
    public function manageAction()
    {
        $this->view->disable();
        $content = [
            'message' => 'Oops! Something went wrong. Please try again later.',
        ];

        $response = new \Phalcon\Http\Response();
        $response->setStatusCode(400, 'Bad Request');

        if ($this->request->isPost() && $this->request->isAjax()) {
            $response->setStatusCode(200);

            $action = $this->request->getPost('action');

            $identity = $this->auth->getIdentity();
            $user_id = $identity['id'];

            $uploadDir = $this->config->application->uploadDir;

            if ($identity['orgId'] != 1) {
                $content = [
                    'status'    => 'fail',
                    'message'   => 'No Permission',
                ];
            } else {
                if ($action == 'add_topic') {
                    if ($this->request->hasFiles() && count($this->request->getUploadedFiles())) {
                        $title = $this->request->getPost('name');

                        // check for duplicate title
                        $existing_topic = EntTopics::count([
                            'conditions' => 'name = ?1 AND is_deleted = 0',
                            'bind'       => [
                                1 => $title,
                            ],
                            'cache'      => false,
                        ]);

                        if ($existing_topic) {
                            $content = [
                                'status'    => 'fail',
                                'message'   => 'A topic with same title already exists',
                            ];
                        } else {
                            $upload_dir = $uploadDir . 'topic/';

                            if (! file_exists($upload_dir)) {
                                mkdir($upload_dir);
                            }

                            $file = $this->request->getUploadedFiles()[0];
                            $filename = 'U' . $user_id . '-' . time() . '.' . $file->getExtension();
                            $file->moveTo($upload_dir . $filename);

                            $topicData = [
                                'name'          => $title,
                                'icon'          => $filename,
                                'created_by'    => $user_id,
                                'updated_by'    => $user_id,
                            ];

                            $topic = new EntTopics();

                            if ($topic->create($topicData) === false) {
                                $content = [
                                    'status'    => 'fail',
                                    'message'   => 'An error occurred while creating topic' . implode("\n", $topic->getMessages()),
                                ];
                            } else {
                                $this->flashSession->success('Topic ' . $title . ' has been successfully created. Your changes may take up to 30 seconds to reflect.');

                                $content = [
                                    'status'    => 'success',
                                ];
                            }
                        }
                    } else {
                        $content = [
                            'status'    => 'fail',
                            'message'   => 'Error while uploading icon',
                        ];
                    }
                } else if ($action == 'edit_topic') {
                    $id = $this->request->getPost('id');
                    $title = $this->request->getPost('name');

                    // check for duplicate title
                    $existing_topic = EntTopics::count([
                        'conditions' => 'pk_id != ?1 AND name = ?2 AND is_deleted = 0',
                        'bind'       => [
                            1 => $id,
                            2 => $title,
                        ],
                        'cache'      => false,
                    ]);

                    if ($existing_topic) {
                        $content = [
                            'status'    => 'fail',
                            'message'   => 'A topic with same title already exists',
                        ];
                    } else {
                        $topic = EntTopics::findFirst([
                            'conditions' => 'pk_id = ?1',
                            'bind'       => [
                                1 => $id,
                            ],
                            'cache'      => false,
                        ]);

                        $topicData = [
                            'name'          => $title,
                            'updated_by'    => $user_id,
                        ];

                        if ($this->request->hasFiles() && count($this->request->getUploadedFiles())) {
                            $upload_dir = $uploadDir . 'topic/';

                            // remove previous icon
                            if ($topic->icon) @unlink($upload_dir . $topic->icon);

                            $file = $this->request->getUploadedFiles()[0];
                            $filename = 'U' . $user_id . '-' . time() . '.' . $file->getExtension();
                            $file->moveTo($upload_dir . $filename);

                            $topicData['icon'] = $filename;
                        }

                        if ($topic->update($topicData) === false) {
                            $content = [
                                'status'    => 'fail',
                                'message'   => 'An error occurred while updating topic',
                            ];
                        }

                        $this->flashSession->success('Topic ' . $title . ' has been successfully updated. Your changes may take up to 30 seconds to reflect.');

                        $content = [
                            'status'    => 'success',
                        ];
                    }
                } else if ($action == 'hide_topic') {
                    $id = $this->request->getPost('id');

                    $topic = EntTopics::findFirst([
                        'conditions' => 'pk_id = ?1',
                        'bind'       => [
                            1 => $id,
                        ],
                        'cache'      => false,
                    ]);

                    if (! $topic) {
                        $content = [
                            'status'    => 'fail',
                            'message'   => 'Invalid request',
                        ];
                    } else {
                        $topicData = [
                            'is_active'     => false,
                        ];

                        if ($topic->update($topicData) === false) {
                            $content = [
                                'status'    => 'fail',
                                'message'   => 'An error occurred while hiding topic',
                            ];
                        }

                        $this->flashSession->success('Topic ' . $topic->name . ' has been hidden. Your changes may take up to 30 seconds to reflect.');

                        $content = [
                            'status'    => 'success',
                        ];
                    }
                } else if ($action == 'show_topic') {
                    $id = $this->request->getPost('id');

                    $topic = EntTopics::findFirst([
                        'conditions' => 'pk_id = ?1',
                        'bind'       => [
                            1 => $id,
                        ],
                        'cache'      => false,
                    ]);

                    if (! $topic) {
                        $content = [
                            'status'    => 'fail',
                            'message'   => 'Invalid request',
                        ];
                    } else {
                        $topicData = [
                            'is_active'     => true,
                        ];

                        if ($topic->update($topicData) === false) {
                            $content = [
                                'status'    => 'fail',
                                'message'   => 'An error occurred while unhiding topic',
                            ];
                        }

                        $this->flashSession->success('Topic ' . $topic->name . ' has been set public. Your changes may take up to 30 seconds to reflect.');

                        $content = [
                            'status'    => 'success',
                        ];
                    }
                } else if ($action == 'delete_topic') {
                    $id = $this->request->getPost('id');

                    $topic = EntTopics::findFirst([
                        'conditions' => 'pk_id = ?1',
                        'bind'       => [
                            1 => $id,
                        ],
                        'cache'      => false,
                    ]);

                    if (! $topic) {
                        $content = [
                            'status'    => 'fail',
                            'message'   => 'Invalid request',
                        ];
                    } else {
                        $topicData = [
                            'is_deleted'     => true,
                        ];

                        if ($topic->update($topicData) === false) {
                            $content = [
                                'status'    => 'fail',
                                'message'   => 'An error occurred while deleting topic',
                            ];
                        }

                        $this->flashSession->success('Topic ' . $topic->name . ' has been deleted. Your changes may take up to 30 seconds to reflect.');

                        $content = [
                            'status'    => 'success',
                        ];
                    }
                } else if ($action == 'hide_article') {
                    $id = $this->request->getPost('id');

                    $article = EntArticles::findFirst([
                        'conditions' => 'pk_id = ?1',
                        'bind'       => [
                            1 => $id,
                        ],
                        'cache'      => false,
                    ]);

                    if (! $article) {
                        $content = [
                            'status'    => 'fail',
                            'message'   => 'Invalid request',
                        ];
                    } else {
                        $articleData = [
                            'is_active'     => false,
                        ];

                        if ($article->update($articleData) === false) {
                            $content = [
                                'status'    => 'fail',
                                'message'   => 'An error occurred while hiding article',
                            ];
                        }

                        $this->flashSession->success('Article ' . $article->title . ' has been hidden. Your changes may take up to 30 seconds to reflect.');

                        $content = [
                            'status'    => 'success',
                        ];
                    }
                } else if ($action == 'show_article') {
                    $id = $this->request->getPost('id');

                    $article = EntArticles::findFirst([
                        'conditions' => 'pk_id = ?1',
                        'bind'       => [
                            1 => $id,
                        ],
                        'cache'      => false,
                    ]);

                    if (! $article) {
                        $content = [
                            'status'    => 'fail',
                            'message'   => 'Invalid request',
                        ];
                    } else {
                        $articleData = [
                            'is_active'     => true,
                        ];

                        if ($article->update($articleData) === false) {
                            $content = [
                                'status'    => 'fail',
                                'message'   => 'An error occurred while unhiding article',
                            ];
                        }

                        $this->flashSession->success('Article ' . $article->title . ' has been set public. Your changes may take up to 30 seconds to reflect.');

                        $content = [
                            'status'    => 'success',
                        ];
                    }
                } else if ($action == 'delete_article') {
                    $id = $this->request->getPost('id');

                    $article = EntArticles::findFirst([
                        'conditions' => 'pk_id = ?1',
                        'bind'       => [
                            1 => $id,
                        ],
                        'cache'      => false,
                    ]);

                    if (! $article) {
                        $content = [
                            'status'    => 'fail',
                            'message'   => 'Invalid request',
                        ];
                    } else {
                        $articleData = [
                            'is_deleted'     => true,
                        ];

                        if ($article->update($articleData) === false) {
                            $content = [
                                'status'    => 'fail',
                                'message'   => 'An error occurred while deleting article',
                            ];
                        }

                        $this->flashSession->success('Article ' . $article->title . ' has been deleted. Your changes may take up to 30 seconds to reflect.');

                        $content = [
                            'status'    => 'success',
                        ];
                    }
                }
            }
        }

        $response->setContent(json_encode($content));
        $response->send();
        exit;
    }

    public function contenttoolsAction()
    {
        $identity = $this->auth->getIdentity();
        $user_id = $identity['id'];

        $this->view->disable();
        $content = [
            'message' => 'Oops! Something went wrong. Please try again later.',
        ];

        $response = new \Phalcon\Http\Response();
        $response->setStatusCode(400, 'Bad Request');

        if ($this->request->isPost()) {
            $response->setStatusCode(200);

            $event = $this->dispatcher->getParam('event');

            $uploadDir = $this->config->application->uploadDir;
            $uploadUrl = $this->config->application->uploadUrl;

            if ($event == 'fileready') {
                $upload_dir = $uploadDir . 'contenttools/temporary/';

                if (! file_exists($uploadDir . 'contenttools/')) {
                    mkdir($uploadDir . 'contenttools/');
                }

                if (! file_exists($upload_dir)) {
                    mkdir($upload_dir);
                }

                $file = $this->request->getUploadedFiles()[0];

                if ($file->getKey() != 'image') {
                    $content = [
                        'status'  => 'failure',
                        'message' => 'Invalid request',
                    ];
                } else {
                    // $filename = $file->getName();
                    $filename = $user_id;

                    // remove previous file
                    if (file_exists($upload_dir . $filename)) unlink($upload_dir . $filename);

                    $file->moveTo($upload_dir . $filename);

                    list($width, $height) = getimagesize($upload_dir . $filename);

                    $content = [
                        'status'=> 'success',
                        'size'  => [
                            $width, $height
                        ],
                        'url'   => $uploadUrl . 'contenttools/temporary/' . $filename,
                    ];
                }
            } else if ($event == 'save') {
                $upload_dir = $uploadDir . 'contenttools/' . $user_id . '/';
                $filename = time();

                if (! file_exists($upload_dir)) {
                    mkdir($upload_dir);
                }

                // move file from temporary folder
                rename($uploadDir . '..' . $this->request->getPost('url'), $upload_dir . $filename);

                $items = list($width, $height) = getimagesize($upload_dir . $filename);

                $content = [
                    'url'   => $uploadUrl . 'contenttools/' . $user_id . '/' . $filename,
                    'width' => $this->request->getPost('width'),
                    'crop'  => $this->request->getPost('crop'),
                    'alt'   => 'Image',
                    'size'  => [
                        $width, $height,
                    ],
                ];
            } else if ($event == 'saveArticle') {
                $type = $this->request->getPost('type');

                $identifier = $this->request->getPost('identifier');
                $article_title = $this->request->getPost('article-title');
                $article_content = $this->request->getPost('article-content');
                $images = $this->request->getPost('images');

                if ($type == 'new') {
                    //--- create article ---

                    $articleData = [
                        'fk_ent_topics_id'  => $identifier,
                        'title'             => $article_title,
                        'content'           => $article_content,
                        'created_by'        => $user_id,
                        'updated_by'        => $user_id,
                    ];

                    $article = new EntArticles();

                    if ($article->create($articleData) === false) {
                        $content = [
                            'status'    => 'fail',
                            'message'   => 'An error occurred while creating article' . implode("\n", $article->getMessages()),
                        ];
                    } else {
                        //--- image manage ---

                        $images = (array)(json_decode($images));

                        if (! empty($images)) {
                            $upload_dir = $uploadDir . 'article/';

                            if (! file_exists($uploadDir . 'article/')) {
                                mkdir($uploadDir . 'article/');
                            }

                            if (! file_exists($upload_dir)) {
                                mkdir($upload_dir);
                            }

                            if (! file_exists($upload_dir . $article->pk_id)) {
                                mkdir($upload_dir . $article->pk_id);
                            }

                            foreach ($images as $image => $width) {
                                $filename = basename($image);

                                // move file from temporary folder
                                rename($uploadDir . '..' . $image, $upload_dir . $article->pk_id . '/' . $filename);

                                $article_content = str_replace($image, $uploadUrl . 'article/' . $article->pk_id . '/' . $filename, $article_content);
                            }

                            $article = EntArticles::findFirst([
                                'conditions' => 'pk_id = ?1 AND is_active = 1 AND is_deleted = 0',
                                'bind'       => [
                                    1        => $article->pk_id,
                                ],
                                'cache' => false,
                            ]);

                            // update article content field
                            $article->content = $article_content;

                            if ($article->update() === false) {
                                $article->delete();

                                $content = [
                                    'status'    => 'fail',
                                    'message'   => 'An error occurred while setting article content',
                                ];
                            } else {
                                $this->flashSession->success('Article has been successfully created. Your changes may take up to 30 seconds to reflect.');

                                $content = [
                                    'status'    => 'success',
                                    'article'   => $article->pk_id,
                                ];
                            }
                        } else {
                            $this->flashSession->success('Article has been successfully created. Your changes may take up to 30 seconds to reflect.');

                            $content = [
                                'status'    => 'success',
                                'article'   => $article->pk_id,
                            ];
                        }

                    }
                } else if ($type == 'edit') {
                    //--- edit article ---

                    $article = EntArticles::findFirst([
                        'conditions' => 'pk_id = ?1 AND is_active = 1 AND is_deleted = 0',
                        'bind'       => [
                            1        => $identifier,
                        ],
                        'cache' => false,
                    ]);

                    $articleData = [
                        'updated_by'        => $user_id,
                    ];

                    if ($article_title) $articleData['title'] = $article_title;
                    if ($article_content) $articleData['content'] = $article_content;

                    $images = (array)(json_decode($images));

                    if (! empty($images)) {
                        $upload_dir = $uploadDir . 'article/';

                        if (! file_exists($uploadDir . 'article/')) {
                            mkdir($uploadDir . 'article/');
                        }

                        if (! file_exists($upload_dir)) {
                            mkdir($upload_dir);
                        }

                        if (! file_exists($upload_dir . $article->pk_id)) {
                            mkdir($upload_dir . $article->pk_id);
                        }

                        foreach ($images as $image => $width) {
                            $filename = basename($image);

                            // move file from temporary folder
                            rename($uploadDir . '..' . $image, $upload_dir . $article->pk_id . '/' . $filename);

                            $article_content = str_replace($image, $uploadUrl . 'article/' . $article->pk_id . '/' . $filename, $article_content);
                        }

                        // update article content field
                        $articleData['content'] = $article_content;
                    }

                    if ($article->update($articleData) === false) {
                        $content = [
                            'status'    => 'fail',
                            'message'   => 'An error occurred while updating article' . implode("\n", $article->getMessages()),
                        ];
                    } else {
                        // $this->flashSession->success('Article has been successfully updated. Your changes may take up to 30 seconds to reflect.');
                        $content = [
                            'status'    => 'success',
                            'article'   => $article->pk_id,
                        ];
                    }
                }
            } else {
                echo 'Event = ' . $event . "\n";
                print_r($this->request->getPost());
                print_r($this->request->getUploadedFiles());
                exit;
            }
        }

        $response->setContent(json_encode($content));
        $response->send();
        exit;
    }

}
