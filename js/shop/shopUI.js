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
  // console.log(e.target.value);
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
    // console.log(err.message);
  }
};

window.addEventListener('keydown', async (e) => {
  if (e.key !== 'Escape') return;
  cancelOrder();
});

const requiredFormElements = ['customerName', 'customerEmail', 'delivery-address1', 'delivery-city', 'delivery-postcode', 'delivery-country'];

const validateEmail = (email) => {
  const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
  return re.test(String(email).toLowerCase());
};

const validateCheckoutForm = (e) => {
  let validated = true;
  for (const requiredFormElementId of requiredFormElements) {
    const requiredFormElement = document.getElementById(requiredFormElementId);
    if (!requiredFormElement.value) {
      requiredFormElement.classList.add('form-error');
      deactivateCheckoutSubmit();
      validated = false;
    } else {
      requiredFormElement.classList.remove('form-error');
    }
  }
  if (!validateEmail(document.getElementById('customerEmail').value)) {
    deactivateCheckoutSubmit();
    document.getElementById('customerEmail').classList.add('form-error');
    validated = false;
  }
  if (!validated) return false;
  activateCheckoutSubmit();
};

const deactivateCheckoutSubmit = () => {
  document.getElementById('confirmSubmit').disabled = true;
  document.getElementById('confirmSubmit').classList.add('disabled');
};

const activateCheckoutSubmit = () => {
  document.getElementById('confirmSubmit').disabled = false;
  document.getElementById('confirmSubmit').classList.remove('disabled');
};
