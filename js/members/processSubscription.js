const processSubscription = async (type, body, order_details, paymentTimeout) => {
  const popOver = document.getElementById('sumup-card');
  const target = document.getElementById('paymentResponse');
  const defeat = document.getElementById('processing-order');
  if (type == 'auth-screen') {
    defeat.style.display = 'none';
    return;
  }
  defeat.style.display = 'flex';
  let res = await updateSubscription(body);
};
