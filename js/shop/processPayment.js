const processPayment = async (type, body, order_details, paymentTimeout) => {
  clearTimeout(paymentTimeout);
  const popOver = document.getElementById('sumup-card');
  const target = document.getElementById('paymentResponse');
  const defeat = document.getElementById('processing-order');
  if (type == 'auth-screen') {
    defeat.style.display = 'none';
    return;
  }
  defeat.style.display = 'flex';
  let res = await updateOrder(body);
  if (res['status'] == 'no_checkout_reference') return;
  if (!res) {
    res = await updateOrder(body);
    if (res['status'] == 'no_checkout_reference') return;
    if (!res) {
      alert('There has been a problem with your order. Your card may have been charged, but the order may not have reached our systems. Please contact info@unbelievabletruth.co.uk for support, quoting order id ' + order_details['order_id'] + ' and transaction id ' + body['transaction_id'] + '. Sorry for any inconvenience');
      await fetch('/functions/interface/shop/destroy_session.php');
      window.location.href = '/shop/';
      return;
    }
  }
  if (res.status != 'success') {
    const output = await getResponseScreen(res.status);
    target.innerHTML = output;
    popOver.style.display = 'none';
    defeat.style.display = 'none';
    return;
  }

  window.location.href = '/shop/success';
};
