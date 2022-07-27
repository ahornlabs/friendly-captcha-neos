# Friendly Captcha integration for Neos Form Framework

This package add a form element which integrates [Friendly Captcha](https://friendlycaptcha.com/) verification to your form.

**Please note: You need an Friendly Captcha account to use these package.**

## Installation

The package can be installed via Composer.

```bash
$ composer require ahornlabs/friendlycaptcha
```

## Configuration
You need to add your Site Key (`siteKey`) and API Key (`secretKey`) from you Friendly Captcha Account.

```yaml
Ahorn:
  FriendlyCaptcha:
    siteKey: 'add-you-site-key'
    secretKey: 'add-you-secret-key'
```

In production environment we strictly recommend to use environment variables to set the values.

## Add form element with Neos.Form.Builder

Create a new form in Neos Backend. Add the new Friendly Captcha form element to you form.

![Captch Element](Documentation/Images/add-frc-fom-element.jpg)



