import { validEmail, submitEmail } from './modules/loader.js';

// add event listeners for form submission

const submit = document.getElementById('emailSubmit');
submit.disabled = true;

document.getElementById('emailList').addEventListener('submit', async (e) => {
  e.preventDefault();
  submit.disabled = true;
  submit.value = '... processing';
  const res = await submitEmail(e.target);

  if (res.status == 'db_error') {
    submit.value = 'there was an error, please try again';
    submit.disabled = false;
    return;
  }
  if (res.status == 'exists') {
    submit.value = `you're already on the list!`;
  }
  if (res.success) {
    submit.value = 'thank you! check email for confirmation';
  }
  document.getElementById('email').disabled = true;
  document.getElementById('name').disabled = true;
});

document.getElementById('email').addEventListener('input', (e) => {
  if (validEmail(e.target.value)) {
    submit.disabled = false;
    submit.value = 'join the list';
  } else {
    submit.disabled = true;
    submit.value = 'enter your email';
  }
});
