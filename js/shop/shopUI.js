const showHideBillingAddress = (e) => {
  if (e.target.checked) {
    document.getElementById('billingAddress').classList.add('hidden');
  } else {
    document.getElementById('billingAddress').classList.remove('hidden');
  }
};

const mirrorDeliveryAddress = (e) => {
  const arr = e.target.id.split('-');
  const target = document.getElementById(`billing-${arr[1]}`);
  target.value = e.target.value;
};

const updateShippingMethods = (e) => {
  console.log(e.target.value);
};

const cancelOrder = async () => {
  clearTimeout(paymentTimeout);
  document.getElementById('sumup-card').style.display = 'none';
  document.getElementById('processing-order').style.display = 'none';

  const postBody = new FormData();
  postBody.append('status', 'FAILED');
  const apiURL = '/functions/interface/shop/update_order.php';
  try {
    const res = await fetch(apiURL, {
      method: 'POST',
      body: postBody,
    });
  } catch (err) {
    console.log(err.message);
  }
};

window.addEventListener('keydown', async (e) => {
  if (e.key !== 'Escape') return;
  cancelOrder();
});
