<?php
if ( ! function_exists( 'form_stock' ) ) {
    /**
     * Display Product Categories
     * Hooked into the `homepage` action in the homepage template
     *
     * @since  1.0.0
     * @param array $args the product section args.
     * @return void
     */
    function form_stock() {

        echo '<div class="gray-box stock-form-background-box">
  <div class="col-full gray-close-box">
  
  <div class="double-gray-box image-form-box" style="background: url(UR_URL); height: 342px; background-size: 100% 100%; background-position: center; background-repeat: no-repeat;"></div>
  
  <div class="double-gray-box form-stock-box">
  <div class="form-title">ДАРИМ СКИДКУ 1000₽ ЗА ПОДПИСКУ</div>
  <div class="form-descr">БУДЬТЕ В КУРСЕ СОБЫТИЙ ПЕРВЫМИ, ПОДПИШИТЕСЬ НА РАССЫЛКУ НОВОСТЕЙ И ПОЛУЧИТЕ СКИДКУ НА ПЕРВУЮ ПОКУПКУ</div>
  <div>'.do_shortcode('UR_CONTACT_F0RM_7_SHORT_CODE').'</div>
  </div>
  
  </div>
  </div>';

    }

}
?>
