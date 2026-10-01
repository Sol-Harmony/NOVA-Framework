<?php
class Services extends Controller
{
    // list of services with prices, taken from 'services' in config/site.php. View: src/view/services/show.phtml
    public function showAction()
    {
        $this->title = t('services.title');
        $this->description = t('services.intro');

        $items = Config::get('site.services', []);
        $this->data['items'] = is_array($items) ? $items : [];
    }
}
