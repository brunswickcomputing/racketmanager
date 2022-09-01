<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en-gb">

<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="x-apple-disable-message-reformatting" />
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <meta name="color-scheme" content="light dark" />
  <meta name="supported-color-schemes" content="light dark" />
  <title><?php echo $title; ?></title>
  <style>
  /* -------------------------------------
  GLOBAL RESETS
  ------------------------------------- */

  /*All the styling goes here*/

  img {
    border: none;
    -ms-interpolation-mode: bicubic;
    max-width: 100%;
  }

  body {
    background-color: #f6f6f6;
    font-family: sans-serif;
    margin: 0 auto !important;
    -webkit-font-smoothing: antialiased;
    font-size: 16px;
    line-height: 1.4;
    margin: 0;
    padding: 0;
    -ms-text-size-adjust: 100%;
    -webkit-text-size-adjust: 100%;
    max-width: 660px;
  }

  table {
    border-collapse: separate;
    mso-table-lspace: 0pt;
    mso-table-rspace: 0pt;
    width: 100%;
  }
  table td {
    font-family: sans-serif;
    font-size: 16px;
    vertical-align: top;
  }

  /* -------------------------------------
  BODY & CONTAINER
  ------------------------------------- */

  .body {
    background-color: #f6f6f6;
    width: 100%;
  }

  /* Set a max-width, and make it display as block so it will automatically stretch to that width, but will also shrink down on a phone or something */
  .container {
    display: block;

    /* makes it centered */
  }

  /* This should also be a block element, so that it will fill 100% of the .container */
  .content {
    box-sizing: border-box;
    display: block;
    padding: 10px;
  }

  /* -------------------------------------
  HEADER, FOOTER, MAIN
  ------------------------------------- */
  .main {
    background: #ffffff;
    border-radius: 3px;
    width: 100%;
  }

  .wrapper {
    box-sizing: border-box;
    padding: 20px;
  }

  .content-block {
    padding-bottom: 10px;
    padding-top: 10px;
  }

  /* -------------------------------------
  TYPOGRAPHY
  ------------------------------------- */
  h1,
  h2,
  h3,
  h4 {
    color: #000000;
    font-family: Source Sans Pro,sans-serif;
    font-weight: 700;
    line-height: 1.4;
    margin: 0;
    margin-bottom: 10px;
  }

  h1 {
    font-size: 35px;
    font-weight: 900;
    text-align: center;
    text-transform: capitalize;
  }
  h2 {
    margin-bottom: 5px;
  }
  h3 {
    padding-top: 10px;
  }
  h4 {
    padding-top: 10px;
    margin-bottom: 0;
  }
  p,
  ul,
  ol {
    font-size: 16px;
    font-weight: normal;
    margin: 0;
    margin-bottom: 15px;
  }
  p li,
  ul li,
  ol li {
    list-style-position: inside;
    margin-left: 5px;
  }
  ul {
    list-style: none;
    padding-left: 0;
  }

  a {
    color: #3498db;
    text-decoration: underline;
  }

  /* -------------------------------------
  BUTTONS
  ------------------------------------- */
  .btn {
    box-sizing: border-box;
    width: 100%;
  }
  .btn > tbody > tr > td {
    padding-bottom: 15px;
  }
  .btn table {
    width: auto;
  }
  .btn table td {
    background-color: #ffffff;
    border-radius: 5px;
    text-align: center;
  }
  .btn a {
    background-color: #ffffff;
    border: solid 1px #3498db;
    border-radius: 5px;
    box-sizing: border-box;
    color: #3498db;
    cursor: pointer;
    display: inline-block;
    font-size: 14px;
    font-weight: bold;
    margin: 0;
    padding: 12px 25px;
    text-decoration: none;
    text-transform: capitalize;
  }

  .btn-primary table td {
    background-color: #3498db;
  }

  .btn-primary a {
    background-color: #3498db;
    border-color: #3498db;
    color: #ffffff;
  }

  /* -------------------------------------
  OTHER STYLES THAT MIGHT BE USEFUL
  ------------------------------------- */
  .strong {
    font-weight: 700;
  }
  .last {
    margin-bottom: 0;
  }

  .first {
    margin-top: 0;
  }

  .align-center {
    text-align: center;
  }

  .align-right {
    text-align: right;
  }

  .align-left {
    text-align: left;
  }

  .clear {
    clear: both;
  }

  .mt0 {
    margin-top: 0;
  }

  .mb0 {
    margin-bottom: 0;
  }
  .team {
      width: 40%;
  }
  .preheader {
    color: transparent;
    display: none;
    height: 0;
    max-height: 0;
    max-width: 0;
    opacity: 0;
    overflow: hidden;
    mso-hide: all;
    visibility: hidden;
    width: 0;
  }

  .powered-by a {
    text-decoration: none;
  }

  hr {
    border: 0;
    border-bottom: 1px solid #f6f6f6;
    margin: 20px 0;
  }

  /* -------------------------------------
  RESPONSIVE AND MOBILE FRIENDLY STYLES
  ------------------------------------- */
  @media only screen and (max-width: 660px) {
    table.body h1 {
      font-size: 28px !important;
      margin-bottom: 10px !important;
    }
    table.body p,
    table.body ul,
    table.body ol,
    table.body td,
    table.body span,
    table.body a {
      font-size: 16px !important;
    }
    table.body .wrapper,
    table.body .article {
      padding: 10px !important;
    }
    table.body .content {
      padding: 0 !important;
    }
    table.body .container {
      padding: 0 !important;
      width: 100% !important;
    }
    table.body .main {
      border-left-width: 0 !important;
      border-radius: 0 !important;
      border-right-width: 0 !important;
    }
    table.body .btn table {
      width: 100% !important;
    }
    table.body .btn a {
      width: 100% !important;
    }
    table.body .img-responsive {
      height: auto !important;
      max-width: 100% !important;
      width: auto !important;
    }
  }

  /* -------------------------------------
  PRESERVE THESE STYLES IN THE HEAD
  ------------------------------------- */
  @media all {
    .ExternalClass {
      width: 100%;
    }
    .ExternalClass,
    .ExternalClass p,
    .ExternalClass span,
    .ExternalClass font,
    .ExternalClass td,
    .ExternalClass div {
      line-height: 100%;
    }
    .apple-link a {
      color: inherit !important;
      font-family: inherit !important;
      font-size: inherit !important;
      font-weight: inherit !important;
      line-height: inherit !important;
      text-decoration: none !important;
    }
    #MessageViewBody a {
      color: inherit;
      text-decoration: none;
      font-size: inherit;
      font-family: inherit;
      font-weight: inherit;
      line-height: inherit;
    }
    .btn-primary table td:hover {
      background-color: #34495e !important;
    }
    .btn-primary a:hover {
      background-color: #34495e !important;
      border-color: #34495e !important;
    }
  }
  @media all{
    body{margin:0;}
    footer,header{display:block;}
    a{background-color:transparent;}
    a:active,a:hover{outline:0;}
    svg:not(:root){overflow:hidden;}
    input{color:inherit;font:inherit;margin:0;}
    input[type=submit]{-webkit-appearance:button;cursor:pointer;}
    input::-moz-focus-inner{border:0;padding:0;}
    @media print{
      *,:after,:before{background:0 0!important;color:#000!important;-webkit-box-shadow:none!important;box-shadow:none!important;text-shadow:none!important;}
      a,a:visited{text-decoration:underline;}
      a[href]:after{content:" (" attr(href) ")";}
      a[href^="#"]:after{content:"";}
      p{orphans:3;widows:3;}
    }
    *,:after,:before{-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box;}
    body{font-family:"Helvetica Neue",Helvetica,Arial,sans-serif;font-size:14px;line-height:1.42857143;color:#333;background-color:#fff;}
    input{font-family:inherit;font-size:inherit;line-height:inherit;}
    a{color:#337ab7;text-decoration:none;}
    a:focus,a:hover{color:#23527c;text-decoration:underline;}
    a:focus{outline:dotted thin;outline:-webkit-focus-ring-color auto 5px;outline-offset:-2px;}
    p{margin:0 0 10px;}
    .container-fluid{margin-right:auto;margin-left:auto;padding-left:15px;padding-right:15px;}
    @media (min-width:992px){
      .container-fluid{padding-left:40px;padding-right:40px;}
    }
    .row{margin-left:-15px;margin-right:-15px;}
    .col-md-4,.col-md-6{position:relative;min-height:1px;padding-left:15px;padding-right:15px;}
    @media (min-width:992px){
      .col-md-4,.col-md-6{float:left;}
      .col-md-6{width:50%;}
      .col-md-4{width:33.33333333%;}
    }
    label{display:inline-block;max-width:100%;margin-bottom:5px;font-weight:700;}
    input[type=search]{-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box;}
    input[type=search]{-webkit-appearance:none;}
    .container-fluid:after,.container-fluid:before,.row:after,.row:before{content:" ";display:table;}
    .container-fluid:after,.row:after{clear:both;}
  }
  @media all{
    .screen-reader-text{border:0;clip:rect(1px,1px,1px,1px);-webkit-clip-path:inset(50%);clip-path:inset(50%);height:1px;margin:-1px;overflow:hidden;padding:0;position:absolute;width:1px;word-wrap:normal!important;}
    .screen-reader-text:focus{background-color:#ddd;clip:auto!important;-webkit-clip-path:none;clip-path:none;color:#444;display:block;font-size:1em;height:auto;left:5px;line-height:normal;padding:15px 23px 14px;text-decoration:none;top:5px;width:auto;z-index:100000;}
  }
  @media all{
    .valign{display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-align:center;-ms-flex-align:center;align-items:center;}
    .screen-reader-text{clip:rect(1px, 1px, 1px, 1px);position:absolute!important;height:1px;width:1px;overflow:hidden;}
    .screen-reader-text:hover,.screen-reader-text:active,.screen-reader-text:focus{background-color:#f1f1f1;border-radius:3px;box-shadow:0 0 2px 2px rgba(0, 0, 0, 0.6);clip:auto!important;color:#21759b;display:block;font-size:14px;font-size:0.875rem;font-weight:bold;height:auto;left:5px;line-height:normal;padding:15px 23px 14px;text-decoration:none;top:5px;width:auto;z-index:100000;}
    *,*:before,*:after{-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box;}
    body{font-size:16px;line-height:1.7;font-family:-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;background:#fff;color:var(--sydney-text-color);}
    a{text-decoration:none;-webkit-transition:all 0.3s ease-in-out;-moz-transition:all 0.3s ease-in-out;-ms-transition:all 0.3s ease-in-out;-o-transition:all 0.3s ease-in-out;transition:all 0.3s ease-in-out;}
    a:hover,a:focus{color:#443f3f;text-decoration:none;outline:0;-webkit-transition:all 0.3s ease-in-out;-moz-transition:all 0.3s ease-in-out;-ms-transition:all 0.3s ease-in-out;-o-transition:all 0.3s ease-in-out;transition:all 0.3s ease-in-out;}
    p{margin-bottom:20px;}
    input[type="submit"]{position:relative;display:inline-block;font-size:13px;line-height:24px;font-weight:700;padding:12px 34px;color:#fff;text-transform:uppercase;border-radius:3px;transition:all 0.3s;}
    input[type="submit"]:hover{background-color:transparent;}
    input[type="search"]{color:#767676;background-color:#fafafa;border:1px solid #dbdbdb;height:50px;padding:4px 20px;border-radius:0;-webkit-transition:all 0.3s ease-in-out;-moz-transition:all 0.3s ease-in-out;-ms-transition:all 0.3s ease-in-out;-o-transition:all 0.3s ease-in-out;transition:all 0.3s ease-in-out;}
    input[type="search"]:focus{-webkit-box-shadow:none;-moz-box-shadow:none;box-shadow:none;}
    input:-moz-placeholder,input::-moz-placeholder{color:#a3a2a2;}
    input:-ms-input-placeholder{color:#c3c3c3;}
    input::-webkit-input-placeholder{color:#cacaca;}
    input[type="submit"],input[type="search"]{-webkit-appearance:none;text-shadow:none;-webkit-box-shadow:none;-moz-box-shadow:none;-o-box-shadow:none;box-shadow:none;}
    .site-title{font-size:32px;line-height:1.2;font-weight:600;}
    .site-title a,.site-title a:hover{color:#fff;}
    .site-title{margin:0;}
    .main-header,.header-search-form{background-color:var(--sydney-dark-background);z-index:999;}
    .header-elements{display:-webkit-box;display:-ms-flexbox;display:flex;-webkit-box-align:center;-ms-flex-align:center;align-items:center;}
    .header-elements svg{fill:#fff;max-height:16px;}
    .site-branding{text-align:center;}
    .header_layout_3 .header-elements:not(:first-of-type){justify-content:flex-end;}
    .main-header .top-header-row{padding-top:15px;padding-bottom:15px;}
    .header-item{margin-right:25px;line-height:1;}
    .header-item:last-child{margin-right:0;}
    .header-search .icon-search,.header-search .icon-cancel{display:none;cursor:pointer;position:relative;z-index:999;}
    .header-search .icon-search.active{display:inline-block;}
    .header-search .icon-cancel svg{width:20px;height:24px;}
    .icon-cancel{cursor:pointer;}
    .header-search-form{position:absolute;width:100%;top:auto;padding:20px;z-index:999999;left:-9999em;opacity:0;transition:opacity 0.3s;visibility:hidden;}
    .header-search-form form{max-width:720px;margin-left:auto;margin-right:auto;display:flex;}
    .header-search-form form .search-field,.header-search-form form label{width:100%;}
    .header-search-form label{margin-bottom:0;}
    .header-search-form form .search-field{display:block;}
    .search-field{max-width:100%;}
    .site-info{padding:20px 0;}
    .site-footer{background-color:#1c1c1c; text-align: center;}
    .site-footer,.site-footer a{color:#666;}
    a{color:#d65050;}
    input[type="submit"]{background-color:#d65050;border:1px solid #d65050;}
    input[type="submit"]:hover{background-color:transparent;color:#d65050;}
    input[type="search"]:focus{border:1px solid #d65050;}
    .sydney-svg-icon{display:inline-block;width:16px;height:16px;vertical-align:middle;line-height:1;}
  }
  input[type="submit"]:hover,a{color:#006800;}
  input[type="submit"]{background-color:#006800;}
  input[type="search"]:focus,input[type="submit"]{border-color:#006800;}
  .site-info{border-top:0;}
  .site-footer{background-color:#1c1c1c;}
  .site-info,.site-info a{color:#ffffff;}
  .site-info{padding-top:20px;padding-bottom:20px;}
  @media (min-width:992px){
    input[type="submit"]{padding-top:12px;padding-bottom:12px;}
  }
  @media (min-width:576px) and (max-width:991px){
    input[type="submit"]{padding-top:12px;padding-bottom:12px;}
  }
  @media (max-width:575px){
    input[type="submit"]{padding-top:12px;padding-bottom:12px;}
  }
  @media (min-width:992px){
    input[type="submit"]{padding-left:35px;padding-right:35px;}
  }
  @media (min-width:576px) and (max-width:991px){
    input[type="submit"]{padding-left:35px;padding-right:35px;}
  }
  @media (max-width:575px){
    input[type="submit"]{padding-left:35px;padding-right:35px;}
  }
  input[type="submit"]{border-radius:0;}
  @media (min-width:992px){
    input[type="submit"]{font-size:14px;}
  }
  @media (min-width:576px) and (max-width:991px){
    input[type="submit"]{font-size:14px;}
  }
  @media (max-width:575px){
    input[type="submit"]{font-size:14px;}
  }
  input[type="submit"]{text-transform:uppercase;}
  input[type="submit"]{background-color:#006800;}
  input[type="submit"]:hover{background-color:#ffffff;}
  input[type="submit"]{color:#ffffff;}
  input[type="submit"]:hover{color:#006800;}
  input[type="submit"]{border-color:#006800;}
  input[type="submit"]:hover{border-color:#006800;}
  .main-header{border-bottom:1px solid #006800;}
  .main-header,.header-search-form{background-color:#006800;}
  .main-header .top-header-row{padding-top:15px;padding-bottom:15px;}
  @media (min-width:992px){
    .site-title{font-size:32px;}
  }
  @media (min-width:576px) and (max-width:991px){
    .site-title{font-size:24px;}
  }
  @media (max-width:575px){
    .site-title{font-size:20px;}
  }
  body{font-family:Source Sans Pro,sans-serif;font-weight:regular;}
  .site-title{line-height:1.2;letter-spacing:px;}
  body{line-height:1.68;letter-spacing:px;}
  @media (min-width:992px){
    body{font-size:16px;}
  }
  @media (min-width:576px) and (max-width:991px){
    body{font-size:16px;}
  }
  @media (max-width:575px){
    body{font-size:16px;}
  }
  input::placeholder{opacity:1;}
  a.button.button--green {
    border-width: 1px;
    border-style: solid;
    padding: 12px 35px;
  }

  .button--green:hover {
    color: #006800;
    background-color: #FFFFFF;
    border-color: #006800;
  }

  .button--green {
    color: #ffffff;
    background-color: #006800;
    border-color: #006800;
  }

  table.body-action {
    margin: 30px auto;
  }

  h1.username {
    font-size: 24px;
    text-align: left;
  }

  table.body-sub {
    border-top: 1px solid #eaeaea;
  }

  .body-sub p {
    color: #6B6E76;
  }
  .bold {
    font-weight: bold;
  }
  table.fixtures td {
    font-size: 12px;
  }
  </style>
</head>
<body class="body">
  <div role="main">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="body">
      <tr>
        <td class="container">
          <div class="content">
            <!-- START header -->
            <div class="header main-header">
              <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                <tr class="">
                  <td class="content-block site-branding site-title"><a><?php echo $organisationName ?></a></td>
                </tr>
              </table>
            </div>
            <!-- END HEADER -->

            <!-- START CENTERED WHITE CONTAINER -->
            <table role="presentation" class="main">
