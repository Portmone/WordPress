# Плагин Portmone.com для WordPress-4.7.3, WP eCommerce-3.12.0

Creator: Portmone.com   
Tags: Portmone, WordPress, eCommerce, payment, payment gateway, credit card, debit card    
Requires at least: WordPress-4.7.3, WP eCommerce-3.12.0    
License: Payment Card Industry Data Security Standard (PCI DSS)     
License URI: [License](https://www.portmone.com.ua/r3/uk/security/)     

Расширение для WordPress позволяет клиентам осуществлять платежи с помощью [Portmone.com](https://www.portmone.com.ua/r3/).

### Описание
Этот модуль добавляет Portmone.com в качестве способа оплаты в ваш магазин WordPress. 
Portmone.com может безопасно, быстро и легко принять VISA и MasterCard в вашем магазине за считанные минуты.
Простые и понятные цены, первоклассный анализ мошенничества и круглосуточная поддержка.
Для работы модуля необходима регистрация в сервисе.

Регистрация в Portmone.com: [Create Free Portmone Account](https://www.portmone.com.ua/r3/ecommerce/sign-up)    
С нами ваши клиенты могут совершать покупки в UAH.

### Ручная установка
Поместить плагин (папку portmone_merchant и фаил portmone.merchant.php) 
в директорию с платежными системами плагина WP eCommerce \wp-content\plugins\wp-e-commerce\wpsc-merchants\

### Настройка модуля
1.  Активировать плагин в административной панеле: Настройки -> Магазин -> Платежи -> Portmone -> Настройки
2.  Заполните:
    - «Идентификатор магазина в системе Portmone(Payee ID)»;
    - «Логин Интернет-магазина в системе Portmone»;
    - «Пароль Интернет-магазина в системе Portmone»;    
    Эти параметры предоставляет менеджер Portmone.com; 
    - прочие поля заполните по своему усмотрению.
3. Нажмите кнопку «Сохранить».

Метод активен и появится в списке оплат вашего магазина.    
P.S. Portmone, принимает только Гривны (UAH).   
P.S. Сумма платежа не конверируется в валюту Гривны(UAH) автоматически. В магазине по умолчанию должна быть валюта Гривны (UAH).
