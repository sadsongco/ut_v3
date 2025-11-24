const updateOrder = async (body) => {
  // body = spoofResponse;
  // console.log('SPOOFED PAYMENT RESPONSE', body);
  if (!body.checkout_reference) return { status: 'no_checkout_reference' };
  const postBody = new FormData();
  for (const [key, value] of Object.entries(body)) {
    postBody.append(key, value);
  }
  const apiURL = '/functions/interface/shop/update_order.php';
  try {
    const res = await fetch(apiURL, {
      method: 'POST',
      body: postBody,
    });

    // return await res.text();
    return await res.json();
  } catch (err) {
    console.log(err.message);
    return false;
  }
};
