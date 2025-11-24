const submitEmail = async (form) => {
  const apiURL = './functions/interface/email_subscribe.php';
  const email = document.getElementById('email').value;
  const name = document.getElementById('name').value;
  const postObj = { email: email, name: name };
  try {
    const res = await fetch(apiURL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(postObj),
    });
    // return console.log(await res.text());
    return await res.json();
  } catch (err) {
    console.error(err);
  }
};

export { submitEmail };
