# Friendly Captcha integration for Neos Form Framework

This package add a form element which integrates [Friendly Captcha](https://friendlycaptcha.com/) verification to your form.

**Please note: You need an Friendly Captcha account to use these package.**

## Installation

The package can be installed via Composer.

```bash
$ composer require ahornlabs/friendlycaptcha
```

## Configuration
You need to add your Site Key (`siteKey`) and API Key (`secretKey`) from you Friendly Captcha account. You can specify the default widget language and when the widget should start solving the puzzle. Please have a look at the official [Friendly Captcha Widget API](https://docs.friendlycaptcha.com/#/widget_api) if you need more informations.

```yaml
Ahorn:
  FriendlyCaptcha:
    siteKey: 'add-you-site-key'
    secretKey: 'add-you-secret-key'
    language: 'de'
    startVerification: 'auto'
```

In production environment we strictly recommend to use environment variables to set the values.

## Add form element with Neos.Form.Builder

Create a new form in Neos backend. Add the new Friendly Captcha form element to you form.

![Captch Element](Documentation/Images/add-frc-fom-element.jpg)

## Language Support

The package supports language content dimensions and set the language of the widget based on the language dimension. At the moment the package support only 2 characters to identify the language. If you are using more then 2 characters (e.g. `en-us`) to define the language, this identifier is cropped to the first two characters.

If you want to overrite the language of the widget, you can do this in the Neos backend.

## Override Settings

You can override the following configurations in the node properties:

* Site Key
* Secret Key
* Language of the Widget
* Verification start
