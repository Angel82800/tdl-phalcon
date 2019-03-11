<?php

namespace Thrust\Controllers;

use Thrust\Forms\ContactsForm;

/**
 * Thrust\Controllers\IntelligenceController.
 */
class IntelligenceController extends ControllerBase
{
    public function initialize()
    {
        $this->view->setTemplateBefore('public');
        $this->tag->setTitle('Todyl | Our Business is Protecting Yours');
        $this->view->setVar('logged_in', is_array($this->auth->getIdentity()));
    }

    public function indexAction()
    {
        // Hard coding content for the skeleton
        $title = 'Title of the Link';
        $subtitle = 'Aliquam erat volutpat.  Nunc eleifend leo vitae magna.  In id erat non orci commodo lobortis.  Proin neque massa, cursus ut, gravida ut, lobortis eget, lacus.  Sed diam.  Praesent fermentum tempor tellus.  Nullam tempus.  Mauris ac felis vel velit tristique imperdiet.  Donec at pede.  Etiam vel neque nec dui dignissim bibendum.  Vivamus id enim.  Phasellus neque orci, porta a, aliquet quis, semper a, massa.';

        $content = 'Nullam eu ante vel est convallis dignissim.  Fusce suscipit, wisi nec facilisis facilisis, est dui fermentum leo, quis tempor ligula erat quis odio.  Nunc porta vulputate tellus.  Nunc rutrum turpis sed pede.  Sed bibendum.  Aliquam posuere.  Nunc aliquet, augue nec adipiscing interdum, lacus tellus malesuada massa, quis varius mi purus non odio.  Pellentesque condimentum, magna ut suscipit hendrerit, ipsum augue ornare nulla, non luctus diam neque sit amet urna.  Curabitur vulputate vestibulum lorem.  Fusce sagittis, libero non molestie mollis, magna orci ultrices dolor, at vulputate neque nulla lacinia eros.  Sed id ligula quis est convallis tempor.  Curabitur lacinia pulvinar nibh.  Nam a sapien.<br><br>Pellentesque dapibus suscipit ligula.  Donec posuere augue in quam.  Etiam vel tortor sodales tellus ultricies commodo.  Suspendisse potenti.  Aenean in sem ac leo mollis blandit.  Donec neque quam, dignissim in, mollis nec, sagittis eu, wisi.  Phasellus lacus.  Etiam laoreet quam sed arcu.  Phasellus at dui in ligula mollis ultricies.  Integer placerat tristique nisl.  Praesent augue.  Fusce commodo.  Vestibulum convallis, lorem a tempus semper, dui dui euismod elit, vitae placerat urna tortor vitae lacus.  Nullam libero mauris, consequat quis, varius et, dictum id, arcu.  Mauris mollis tincidunt felis.  Aliquam feugiat tellus ut neque.  Nulla facilisis, risus a rhoncus fermentum, tellus tellus lacinia purus, et dictum nunc justo sit amet elit.';

        $q1 = 'Nam euismod tellus id erat.';
        $q2 = 'Sed id ligula quis est convallis tempor.';
        $q3 = 'Donec at pede.In id erat non orci commodo lobortis.';

        $form = new ContactsForm();

        $this->view->setVars(
            array(
                'form'             => $form,
                'title'            => $title,
                'subtitle'         => $subtitle,
                'content'          => $content,
                'similarQuestions' => array(
                    $q1,
                    $q2,
                    $q3
                )
            )
        );
    }
}
