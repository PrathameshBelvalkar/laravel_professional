<!DOCTYPE html>
<html class="no-js" lang="en">

<head>
    <!-- Meta Tags -->
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="author" content="ThemeMarch">

    <title>Broadcasting</title>
</head>
<style>
    @import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap");

    *,
    ::after,
    ::before {
        box-sizing: border-box;
    }

    html {
        line-height: 1.15;
        -webkit-text-size-adjust: 100%;
    }

    body {
        margin: 0;
    }

    main {
        display: block;
    }

    h1 {
        font-size: 2em;
        margin: 0.67em 0;
    }

    hr {
        box-sizing: content-box;
        height: 0;
        overflow: visible;
    }

    pre {
        font-family: monospace, monospace;
        font-size: 1em;

    }

    a {
        background-color: transparent;
    }


    abbr[title] {
        border-bottom: none;

        text-decoration: underline;

        -webkit-text-decoration: underline dotted;
        text-decoration: underline dotted;

    }


    b,
    strong {
        font-weight: bolder;
    }



    code,
    kbd,
    samp {
        font-family: monospace, monospace;
        /* 1 */
        font-size: 1em;
        /* 2 */
    }



    small {
        font-size: 80%;
    }


    sub,
    sup {
        font-size: 75%;
        line-height: 0;
        position: relative;
        vertical-align: baseline;
    }

    sub {
        bottom: -0.25em;
    }

    sup {
        top: -0.5em;
    }



    img {
        border-style: none;
    }



    button,
    input,
    optgroup,
    select,
    textarea {
        font-family: inherit;

        font-size: 100%;

        line-height: 1.15;

        margin: 0;

    }



    button,
    input {

        overflow: visible;
    }



    button,
    select {

        text-transform: none;
    }



    button,
    [type=button],
    [type=reset],
    [type=submit] {
        -webkit-appearance: button;
    }



    button::-moz-focus-inner,
    [type=button]::-moz-focus-inner,
    [type=reset]::-moz-focus-inner,
    [type=submit]::-moz-focus-inner {
        border-style: none;
        padding: 0;
    }



    button:-moz-focusring,
    [type=button]:-moz-focusring,
    [type=reset]:-moz-focusring,
    [type=submit]:-moz-focusring {
        outline: 1px dotted ButtonText;
    }



    fieldset {
        padding: 0.35em 0.75em 0.625em;
    }



    legend {
        box-sizing: border-box;

        color: inherit;

        display: table;

        max-width: 100%;

        padding: 0;

        white-space: normal;

    }

    progress {
        vertical-align: baseline;
    }


    textarea {
        overflow: auto;
    }


    [type=checkbox],
    [type=radio] {
        box-sizing: border-box;

        padding: 0;

    }



    [type=number]::-webkit-inner-spin-button,
    [type=number]::-webkit-outer-spin-button {
        height: auto;
    }



    [type=search] {
        -webkit-appearance: textfield;

        outline-offset: -2px;

    }


    [type=search]::-webkit-search-decoration {
        -webkit-appearance: none;
    }


    ::-webkit-file-upload-button {
        -webkit-appearance: button;

        font: inherit;

    }


    details {
        display: block;
    }

    summary {
        display: list-item;
    }


    template {
        display: none;
    }

    [hidden] {
        display: none;
    }

    body,
    html {
        color: #777777;
        font-family: "Inter", sans-serif;
        font-size: 14px;
        font-weight: 400;
        line-height: 1.5em;
        overflow-x: hidden;
        background-color: #f5f7ff;
    }

    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
        clear: both;
        color: #111111;
        padding: 0;
        margin: 0 0 20px 0;
        font-weight: 500;
        line-height: 1.2em;
    }

    h1 {
        font-size: 60px;
    }

    h2 {
        font-size: 48px;
    }

    h3 {
        font-size: 30px;
    }

    h4 {
        font-size: 24px;
    }

    h5 {
        font-size: 18px;
    }

    h6 {
        font-size: 16px;
    }

    p,
    div {
        margin-top: 0;
        line-height: 1.5em;
    }

    p {
        margin-bottom: 15px;
    }

    ul {
        margin: 0 0 25px 0;
        padding-left: 20px;
        list-style: square outside none;
    }

    ol {
        padding-left: 20px;
        margin-bottom: 25px;
    }

    dfn,
    cite,
    em,
    i {
        font-style: italic;
    }

    blockquote {
        margin: 0 15px;
        font-style: italic;
        font-size: 20px;
        line-height: 1.6em;
        margin: 0;
    }

    address {
        margin: 0 0 15px;
    }

    img {
        border: 0;
        max-width: 100%;
        height: auto;
        vertical-align: middle;
    }

    a {
        color: inherit;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    a:hover {
        color: #2ad19d;
    }

    button {
        color: inherit;
        transition: all 0.3s ease;
    }

    a:hover {
        text-decoration: none;
        color: inherit;
    }

    table {
        width: 100%;
        caption-side: bottom;
        border-collapse: collapse;
    }

    th {
        text-align: left;
    }

    td {
        border-top: 1px solid #eaeaea;
    }

    td,
    th {
        padding: 10px 15px;
        line-height: 1.55em;
    }

    dl {
        margin-bottom: 25px;
    }

    dl dt {
        font-weight: 600;
    }

    b,
    strong {
        font-weight: bold;
    }

    pre {
        color: #777777;
        border: 1px solid #eaeaea;
        font-size: 18px;
        padding: 25px;
        border-radius: 5px;
    }

    kbd {
        font-size: 100%;
        background-color: #777777;
        border-radius: 5px;
    }

    .cs-f10 {
        font-size: 10px;
    }

    .cs-f11 {
        font-size: 11px;
    }

    .cs-f12 {
        font-size: 12px;
    }

    .cs-f13 {
        font-size: 13px;
    }

    .cs-f14 {
        font-size: 14px;
    }

    .cs-f15 {
        font-size: 15px;
    }

    .cs-f16 {
        font-size: 16px;
    }

    .cs-f17 {
        font-size: 17px;
    }

    .cs-f18 {
        font-size: 18px;
    }

    .cs-f19 {
        font-size: 19px;
    }

    .cs-f20 {
        font-size: 20px;
    }

    .cs-f21 {
        font-size: 21px;
    }

    .cs-f22 {
        font-size: 22px;
    }

    .cs-f23 {
        font-size: 23px;
    }

    .cs-f24 {
        font-size: 24px;
    }

    .cs-f25 {
        font-size: 25px;
    }

    .cs-f26 {
        font-size: 26px;
    }

    .cs-f27 {
        font-size: 27px;
    }

    .cs-f28 {
        font-size: 28px;
    }

    .cs-f29 {
        font-size: 29px;
    }

    .cs-light {
        font-weight: 300;
    }

    .cs-normal {
        font-weight: 400;
    }

    .cs-medium {
        font-weight: 500;
    }

    .cs-semi_bold {
        font-weight: 600;
    }

    .cs-bold {
        font-weight: 700;
    }

    .cs-m0 {
        margin: 0px !important;
    }

    .cs-mb0 {
        margin-bottom: 0px;
    }

    .cs-mb1 {
        margin-bottom: 1px;
    }

    .cs-mb2 {
        margin-bottom: 2px;
    }

    .cs-mb3 {
        margin-bottom: 3px;
    }

    .cs-mb4 {
        margin-bottom: 4px;
    }

    .cs-mb5 {
        margin-bottom: 5px;
    }

    .cs-mb6 {
        margin-bottom: 6px;
    }

    .cs-mb7 {
        margin-bottom: 7px;
    }

    .cs-mb8 {
        margin-bottom: 8px;
    }

    .cs-mb9 {
        margin-bottom: 9px;
    }

    .cs-mb10 {
        margin-bottom: 10px;
    }

    .cs-mb11 {
        margin-bottom: 11px;
    }

    .cs-mb12 {
        margin-bottom: 12px;
    }

    .cs-mb13 {
        margin-bottom: 13px;
    }

    .cs-mb14 {
        margin-bottom: 14px;
    }

    .cs-mb15 {
        margin-bottom: 15px;
    }

    .cs-mb16 {
        margin-bottom: 16px;
    }

    .cs-mb17 {
        margin-bottom: 17px;
    }

    .cs-mb18 {
        margin-bottom: 18px;
    }

    .cs-mb19 {
        margin-bottom: 19px;
    }

    .cs-mb20 {
        margin-bottom: 20px;
    }

    .cs-mb21 {
        margin-bottom: 21px;
    }

    .cs-mb22 {
        margin-bottom: 22px;
    }

    .cs-mb23 {
        margin-bottom: 23px;
    }

    .cs-mb24 {
        margin-bottom: 24px;
    }

    .cs-mb25 {
        margin-bottom: 25px;
    }

    .cs-mb26 {
        margin-bottom: 26px;
    }

    .cs-mb27 {
        margin-bottom: 27px;
    }

    .cs-mb28 {
        margin-bottom: 28px;
    }

    .cs-mb29 {
        margin-bottom: 29px;
    }

    .cs-mb30 {
        margin-bottom: 30px;
    }

    .cs-mb40 {
        margin-bottom: 40px;
    }

    .cs-mb50 {
        margin-bottom: 50px;
    }

    .cs-mb70 {
        margin-bottom: 70px;
    }

    .cs-mb80 {
        margin-bottom: 100px;
    }

    .cs-mr5 {
        margin-right: 5px;
    }

    .cs-mr10 {
        margin-right: 10px;
    }

    .cs-mr15 {
        margin-right: 15px;
    }

    .cs-mr20 {
        margin-right: 20px;
    }

    .cs-mr22 {
        margin-right: 22px;
    }

    .cs-mr28 {
        margin-right: 28px;
    }

    .cs-mt30 {
        margin-top: 30px;
    }

    .cs-mt50 {
        margin-top: 50px;
    }

    .cs-mr50 {
        margin-right: 50px;
    }

    .cs-mr60 {
        margin-right: 50px;
    }

    .cs-mr120 {
        margin-right: 120px;
    }

    .cs-mr97 {
        margin-right: 97px;
    }

    .cs-ml10 {
        margin-left: 10px;
    }

    .cs-mt5 {
        margin-top: 5px;
    }

    .cs-mt12 {
        margin-top: 12px;
    }

    .cs-mt20 {
        margin-top: 20px;
    }

    .cs-mt25 {
        margin-top: 25px;
    }

    .cs-mt30 {
        margin-top: 30px;
    }

    .cs-mt100 {
        margin-top: 100px;
    }

    .cs-pt25 {
        padding-top: 25px;
    }

    .cs-p0 {
        padding: 0px !important;
    }

    .cs-p50 {
        padding: 50px !important;
    }

    .cs-p-t5 {
        padding-top: 5px !important;
    }

    .cs-p-t10 {
        padding-top: 10px !important;
    }

    .cs-p-b5 {
        padding-bottom: 5px !important;
    }

    .cs-p-b10 {
        padding-bottom: 10px !important;
    }

    .cs-p-25-50 {
        padding: 25px 50px !important;
    }

    .cs-width_1 {
        width: 8%;
    }

    .cs-width_2 {
        width: 16.66666667%;
    }

    .cs-width_3 {
        width: 25%;
    }

    .cs-width_4 {
        width: 33.33333333%;
    }

    .cs-width_5 {
        width: 41.66666667%;
    }

    .cs-width_6 {
        width: 50%;
    }

    .cs-width_7 {
        width: 58.33333333%;
    }

    .cs-width_8 {
        width: 66.66666667%;
    }

    .cs-width_9 {
        width: 75%;
    }

    .cs-width_10 {
        width: 83.33333333%;
    }

    .cs-width_11 {
        width: 91.66666667%;
    }

    .cs-width_12 {
        width: 100%;
    }

    .cs-accent_color,
    .cs-accent_color_hover:hover {
        color: #2ad19d;
    }

    .cs-accent_bg,
    .cs-accent_bg_hover:hover {
        background-color: #2ad19d;
    }

    .cs-primary_color {
        color: #111111;
    }

    .cs-secondary_color {
        color: #777777;
    }

    .cs-ternary_color {
        color: #353535;
    }

    .cs-dip_green_color {
        color: #2AD19D;
    }

    .cs-ternary_color {
        border-color: #eaeaea;
    }

    .cs-focus_bg {
        background: #f6f6f6;
    }

    .cs-white_bg {
        background: #ffffff;
    }

    .cs-accent_10_bg {
        background-color: rgba(42, 209, 157, 0.1);
    }

    .cs-container {
        max-width: 880px;
        padding: 30px 15px;
        margin-left: auto;
        margin-right: auto;
        z-index: 10;
    }

    .cs-container.style1 {
        max-width: 400px;
    }

    .cs-text_center {
        text-align: center;
    }

    .cs-text_right {
        text-align: right;
    }

    .cs-border_bottom_0 {
        border-bottom: 0;
    }

    .cs-border_top_0 {
        border-top: 0;
    }

    .cs-border_bottom {
        border-bottom: 1px solid #eaeaea;
    }

    .cs-border_top {
        border-top: 1px solid #eaeaea;
    }

    .cs-border_left {
        border-left: 1px solid #eaeaea;
    }

    .cs-border_right {
        border-right: 1px solid #eaeaea;
    }

    .cs-table_baseline {
        vertical-align: baseline;
    }

    .cs-round_border {
        border: 1px solid #eaeaea;
        overflow: hidden;
        border-radius: 6px;
    }

    .cs-border_none {
        border: none;
    }

    .cs-border_left_none {
        border-left-width: 0;
    }

    .cs-border_right_none {
        border-right-width: 0;
    }

    .cs-invoice.cs-style1 {
        background: #fff;
        border-radius: 10px;
        padding: 50px;
    }

    .cs-invoice.cs-style1.padding_40 {
        padding: 40px;
    }

    .cs-invoice.cs-style1 .cs-invoice_head {
        display: flex;
        justify-content: space-between;
    }

    .cs-invoice.cs-style1 .cs-invoice_head.cs-type1 {
        align-items: flex-end;
        padding-bottom: 25px;
        border-bottom: 1px solid #eaeaea;
    }

    .cs-invoice.cs-style1 .cs-invoice_head.cs-type1.border-bottom-none {
        border-bottom: none;
    }

    .cs-invoice.cs-style1 .cs-invoice_footer {
        display: flex;
    }

    .cs-invoice.cs-style1 .cs-invoice_footer table {
        margin-top: -1px;
    }

    .cs-invoice.cs-style1 .cs-left_footer {
        width: 55%;
        padding: 10px 15px;
    }

    .cs-invoice.cs-style1 .cs-right_footer {
        width: 46%;
    }

    .cs-invoice.cs-style1 .cs-note {
        display: flex;
        align-items: flex-start;
        margin-top: 40px;
    }

    .cs-invoice.cs-style1 .cs-note_left {
        margin-right: 10px;
        margin-top: 6px;
        margin-left: -5px;
        display: flex;
    }

    .cs-invoice.cs-style1 .cs-note_left svg {
        width: 32px;
    }

    .cs-invoice.cs-style1 .cs-invoice_left {
        max-width: 55%;
    }

    .cs-invoice.cs-style1 .cs-invoice_left.w-60 {
        max-width: 60%;
    }

    .cs-invoice.cs-style1 .cs-invoice_left.w-65 {
        max-width: 65%;
    }

    .cs-invoice.cs-style1 .cs-invoice_left.w-70 {
        max-width: 70%;
    }

    .cs-invoice.cs-style1 .cs-invoice_left.w-75 {
        max-width: 75%;
    }

    .cs-invoice.cs-style1 .cs-invoice_left.w-80 {
        max-width: 80%;
    }

    .cs-invoice_btns {
        display: flex;
        justify-content: center;
        margin-top: 30px;
    }

    .cs-invoice_btns .cs-invoice_btn:first-child {
        border-radius: 5px 0 0 5px;
    }

    .cs-invoice_btns .cs-invoice_btn:last-child {
        border-radius: 0 5px 5px 0;
    }

    .cs-invoice_btn {
        display: inline-flex;
        align-items: center;
        border: none;
        font-weight: 600;
        padding: 8px 20px;
        cursor: pointer;
    }

    .cs-invoice_btn svg {
        width: 24px;
        margin-right: 5px;
    }

    .cs-invoice_btn.cs-color1 {
        color: #111111;
        background: rgba(42, 209, 157, 0.15);
    }

    .cs-invoice_btn.cs-color1:hover {
        background-color: rgba(42, 209, 157, 0.3);
    }

    .cs-invoice_btn.cs-color2 {
        color: #fff;
        background: #2ad19d;
    }

    .cs-invoice_btn.cs-color2:hover {
        background-color: rgba(42, 209, 157, 0.8);
    }

    .cs-table_responsive {
        overflow-x: auto;
    }

    .cs-table_responsive>table {
        min-width: 600px;
    }

    .cs-50_col>* {
        width: 50%;
        flex: none;
    }

    .cs-bar_list {
        margin: 0;
        padding: 0;
        list-style: none;
        position: relative;
    }

    .cs-bar_list::before {
        content: "";
        height: 75%;
        width: 2px;
        position: absolute;
        left: 4px;
        top: 50%;
        transform: translateY(-50%);
        background-color: #eaeaea;
    }

    .cs-bar_list li {
        position: relative;
        padding-left: 25px;
    }

    .cs-bar_list li:before {
        content: "";
        height: 10px;
        width: 10px;
        border-radius: 50%;
        background-color: #eaeaea;
        position: absolute;
        left: 0;
        top: 6px;
    }

    .cs-bar_list li:not(:last-child) {
        margin-bottom: 10px;
    }

    .cs-table.cs-style1.cs-type1 {
        padding: 10px 30px;
    }

    .cs-table.cs-style1.cs-type1 tr:first-child td {
        border-top: none;
    }

    .cs-table.cs-style1.cs-type1 tr td:first-child {
        padding-left: 0;
    }

    .cs-table.cs-style1.cs-type1 tr td:last-child {
        padding-right: 0;
    }

    .cs-table.cs-style1.cs-type2>* {
        padding: 0 10px;
    }

    .cs-table.cs-style1.cs-type2 .cs-table_title {
        padding: 20px 0 0 15px;
        margin-bottom: -5px;
    }

    .cs-table.cs-style2 td {
        border: none;
    }

    .cs-table.cs-style2 td,
    .cs-table.cs-style2 th {
        padding: 12px 15px;
        line-height: 1.55em;
    }

    .cs-table.cs-style2 tr:not(:first-child) {
        border-top: 1px dashed #eaeaea;
    }

    .cs-list.cs-style1 {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .cs-list.cs-style1 li {
        display: flex;
    }

    .cs-list.cs-style1 li:not(:last-child) {
        border-bottom: 1px dashed #eaeaea;
    }

    .cs-list.cs-style1 li>* {
        flex: none;
        width: 50%;
        padding: 7px 0px;
    }

    .cs-list.cs-style2 {
        list-style: none;
        margin: 0 0 30px 0;
        padding: 12px 0;
        border: 1px solid #eaeaea;
        border-radius: 5px;
    }

    .cs-list.cs-style2 li {
        display: flex;
    }

    .cs-list.cs-style2 li>* {
        flex: 1;
        padding: 5px 25px;
    }

    .cs-heading.cs-style1 {
        line-height: 1.5em;
        border-top: 1px solid #eaeaea;
        border-bottom: 1px solid #eaeaea;
        padding: 10px 0;
    }

    .cs-no_border {
        border: none !important;
    }

    .cs-grid_row {
        display: grid;
        grid-gap: 20px;
        list-style: none;
        padding: 0;
    }

    .cs-col_2 {
        grid-template-columns: repeat(2, 1fr);
    }

    .cs-col_3 {
        grid-template-columns: repeat(3, 1fr);
    }

    .cs-col_4 {
        grid-template-columns: repeat(4, 1fr);
    }

    .cs-border_less td {
        border-color: transparent;
    }

    .cs-special_item {
        position: relative;
    }

    .cs-special_item:after {
        content: "";
        height: 52px;
        width: 1px;
        background-color: #eaeaea;
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        right: 0;
    }

    .cs-table.cs-style1 .cs-table.cs-style1 tr:not(:first-child) td {
        border-color: #eaeaea;
    }

    .cs-table.cs-style1 .cs-table.cs-style2 td {
        padding: 12px 0px;
    }

    .cs-ticket_wrap {
        display: flex;
    }

    .cs-ticket_left {
        flex: 1;
    }

    .cs-ticket_right {
        flex: none;
        width: 215px;
    }

    .cs-box.cs-style1 {
        border: 2px solid #eaeaea;
        border-radius: 5px;
        padding: 20px 10px;
        min-width: 150px;
    }

    .cs-box.cs-style1.cs-type1 {
        padding: 12px 10px 10px;
    }

    .cs-max_w_150 {
        max-width: 150px;
    }

    .cs-left_auto {
        margin-left: auto;
    }

    .cs-title_1 {
        display: inline-block;
        border-bottom: 1px solid #eaeaea;
        min-width: 60%;
        padding-bottom: 5px;
        margin-bottom: 10px;
    }

    .cs-box2_wrap {
        display: grid;
        grid-gap: 30px;
        list-style: none;
        padding: 0;
        grid-template-columns: repeat(2, 1fr);
    }

    .cs-box.cs-style2 {
        border: 1px solid #eaeaea;
        padding: 25px 30px;
        border-radius: 5px;
    }

    .cs-box.cs-style2 .cs-table.cs-style2 td {
        padding: 12px 0;
    }


    /* Sifat */

    .tm-align-item-center {
        display: flex;
        align-items: center;
    }

    .tm-bg-gray {
        background-color: #f7f7f7;
    }

    .tm-border-radious-12 {
        border-radius: 12px;
    }

    .tm-padding-outside {
        padding: 12px 15px;
    }

    .tm-button-gray {
        background-color: #f7f7f7;
        border-radius: 12px;
        padding: 12px 26px 12px 26px;
    }

    .tm-button-gray a {
        color: #111111;
        font-size: 16px;
        font-weight: 500;
    }

    .tm-button-dark {
        background-color: #111111;
        border-radius: 8px;
        padding: 12px 26px 12px 26px;
    }

    .tm-button-dark a {
        color: #fff;
        font-size: 16px;
        font-weight: 500;
    }

    .tm-button-primary {
        background-color: #2ad19d;
        border-radius: 8px;
        padding: 12px 26px 12px 26px !important;
    }

    .tm-button-primary span {
        color: #fff;
        font-size: 16px;
        font-weight: 500;
    }

    .tm-button-primary svg {
        color: #fff;
    }

    .cs-invoice_btn .tm-button-primary :last-child {
        border-radius: 50px !important;
    }

    .tm-border {
        border: 1px solid #eaeaea;
    }

    .tm-bg-none {
        background: none !important;
    }

    .tm-p-16 p {
        font-size: 14px;
    }

    .tm-p-16 td {
        line-height: 10px !important;
    }

    .tm-consulting .tm-custom-td-padding {
        padding: 10px 0px;
    }

    .tm-consulting .tm-custom-td-padding td {
        line-height: 10px;
    }

    .tm-consulting .tm-consult-thead {
        margin: 20px;
        padding: 20px;
    }

    .tm-caption-txt {
        background-color: rgba(42, 209, 157, 0.1);
        border-radius: 5px;
        padding: 5px 10px;
    }

    .tm-border-1px {
        height: 1px;
        background-color: #ececef;
    }

    .tm-border-none tr:not(:first-child) {
        border-top: none !important;
    }

    .top {
        top: 0px;
    }


    /* Position */

    .position-relative {
        position: relative;
    }

    .position-absolute {
        position: absolute;
    }

    .text-transform-uppercase {
        text-transform: uppercase;
    }

    .cs-table.cs-style2.padding-rignt-left td {
        padding: 12px 0px;
    }

    .cs-table.cs-style2.padding-rignt-left th {
        padding: 12px 0px;
    }


    /* Header style */

    .top-header-section {
        position: relative;
        width: 100%;
        -o-object-fit: cover;
        object-fit: cover;
        background-position: center;
        height: 130px;
        background-repeat: no-repeat;
        /* background-image: url(./img/Subtract.png); */
        background-image: url(../img/Subtract.png);
    }

    .top-header-section .header-text {
        position: relative;
        display: flex;
        justify-content: space-between;
        width: 100%;
    }

    .top-bottom-section {
        position: relative;
        width: 100%;
        -o-object-fit: cover;
        object-fit: cover;
        background-position: center;
        height: 130px;
        background-repeat: no-repeat;
        /*    background-image: url(/assets/img/bg-bottom.png); */
        background-image: url(../img/bg-bottom.png);
    }

    .flex-horizontal-center {
        width: 100%;
        display: flex;
        justify-content: center;
    }

    .cs-signature .signature-img {
        width: 155.956px;
        padding-bottom: 20px;
        border-bottom: 1px solid #000;
    }

    .cs-signature P {
        padding-top: 10px;
    }


    /* Border Start */

    .cs-border-1 {
        content: "";
        height: 1px;
        width: 100%;
        margin-top: 9px;
        border: 0.5px dashed rgba(73, 73, 73, 0.768627451);
    }

    .cs-border {
        content: "";
        height: 1px;
        width: 100%;
        border: 1px dashed rgba(73, 73, 73, 0.768627451);
    }

    .cs-border.border-none {
        border: 0px dashed rgba(73, 73, 73, 0.768627451);
    }

    .cs-border_bottom.style_1 {
        border-bottom: 1px dashed rgba(73, 73, 73, 0.768627451);
    }


    /* flex Satrt */

    .display-flex {
        display: flex;
    }

    .space-between {
        justify-content: space-between;
    }

    .align-items-flex-end {
        align-items: flex-end;
    }

    .justify-content-flex-end {
        justify-content: flex-end;
    }

    .justify-content-flex-start {
        justify-content: flex-start;
    }

    .justify-content-space-between {
        justify-content: space-between;
    }

    .justify-content-center {
        justify-content: center;
    }

    .flex-wrap {
        flex-wrap: wrap;
    }

    .gap-30 {
        gap: 30px;
    }

    .gap-40 {
        gap: 40px;
    }

    .gap-50 {
        gap: 50px;
    }

    .gap-60 {
        gap: 60px;
    }

    .gap-135 {
        gap: 135px;
    }


    /* Sifat */

    .cs-ml30 {
        margin-left: 30px;
    }

    .cs-border-50percent {
        height: 1px;
        background-color: #777777;
        width: 100%;
    }

    .cs-mt15 {
        margin-top: 10px;
    }

    .align-item-center {
        align-items: center;
    }

    .justify-content {
        justify-content: center;
    }

    .cs-padding-outside {
        border: 1px dashed #ececef;
        padding: 30px 0px;
        border-radius: 5px;
    }

    .space-between {
        justify-content: space-between;
    }

    .cs-uppercase {
        text-transform: uppercase;
    }

    .cs-mt70 {
        margin-top: 70px;
    }

    .max-width120 {
        max-width: 120px;
    }

    .max-width90 {
        max-width: 90px;
    }

    .cs-top-bg {
        background-image: url(../img/top-bg.png);
        background-position: center top;
        background-repeat: no-repeat;
    }

    .cs-bottom-bg {
        background-image: url(../img/bottom-bg.png);
        background-position: center bottom;
        background-repeat: no-repeat;
    }

    .cs-top-bg2 {
        background-image: url(../img/bg-top-2.png);
        background-position: center top;
        background-repeat: no-repeat;
    }

    .cs-bottom-bg2 {
        background-image: url(../img/bg-bottom-2.png);
        background-position: center bottom;
        background-repeat: no-repeat;
    }

    .cs-bg-none {
        background: none !important;
    }

    .cs-bg-white {
        background: #fff;
    }

    .cs-border-radious25 {
        border-radius: 25px !important;
    }

    .btn-blanck {
        background: none;
    }

    .border-bottom-1 {
        border-bottom: 1px solid #ececef;
    }

    .cs-fuss {
        width: 1px;
        height: 20px;
        background-color: #ececef;
        margin: 0px 20px;
    }

    .cs-text-cap {
        border-bottom: 1px solid #2ad19d;
        margin-top: 20px;
    }

    .copybtn {
        cursor: pointer;
    }

    .cs-logo img {
        height: 3rem;
    }

    .program-avatar {
        object-fit: cover;
        object-position: top center;
        /* border-radius: 50%; */
        /* width: 150px; */
        height: 43px;
    }

    .program-thumnail {
        text-align: center;
    }

    @media (max-width: 767px) {
        .cs-mobile_hide {
            display: none;
        }

        .cs-invoice.cs-style1 {
            padding: 30px 20px;
        }

        .cs-invoice.cs-style1 .cs-right_footer {
            width: 100%;
        }
    }

    @media (max-width: 500px) {
        .cs-invoice.cs-style1 .cs-logo {
            margin-bottom: 10px;
        }

        .cs-invoice.cs-style1 .cs-invoice_head {
            flex-direction: column;
        }

        .cs-invoice.cs-style1 .cs-invoice_head.cs-type1 {
            flex-direction: column-reverse;
            align-items: center;
            text-align: center;
        }

        .cs-invoice.cs-style1 .cs-invoice_head.cs-type1.column {
            flex-direction: column;
            gap: 15px;
        }

        .cs-invoice.cs-style1 .cs-invoice_head .cs-text_right {
            text-align: left;
        }

        .cs-list.cs-style2 li {
            flex-direction: column;
        }

        .cs-list.cs-style2 li>* {
            padding: 5px 20px;
        }

        .cs-grid_row {
            grid-gap: 0px;
        }

        .cs-col_2,
        .cs-col_3,
        .cs-col_4 {
            grid-template-columns: repeat(1, 1fr);
        }

        .cs-table.cs-style1.cs-type1 {
            padding: 0px 20px;
        }

        .cs-box2_wrap {
            grid-template-columns: repeat(1, 1fr);
        }

        .cs-box.cs-style1.cs-type1 {
            max-width: 100%;
            width: 100%;
        }

        .cs-invoice.cs-style1 .cs-invoice_left {
            max-width: 100%;
            flex-wrap: wrap;
            justify-content: center;
        }

        .cs-invoice.cs-style1 .cs-invoice_left.w-60 {
            max-width: 100%;
        }

        .cs-invoice.cs-style1 .cs-invoice_left.w-65 {
            max-width: 100%;
        }

        .cs-invoice.cs-style1 .cs-invoice_left.w-70 {
            max-width: 100%;
        }

        .cs-invoice.cs-style1 .cs-invoice_left.w-75 {
            max-width: 100%;
        }

        .cs-invoice.cs-style1 .cs-invoice_left.w-80 {
            max-width: 100%;
        }

        .cs-ml22 {
            margin-left: 0px;
        }

        .cs-mr15 {
            margin: 0px;
        }

        .cs-mt100 {
            margin-top: 50px;
        }

        .gap-135 {
            gap: 30px;
        }

        .mq-align-items {
            align-items: flex-end;
        }
    }

    @media print {
        .cs-hide_print {
            display: none !important;
        }

        .cs-p-25-50 {
            padding: 25px !important;
        }

        body {
            background-color: #ffffff;
            height: 100%;
            overflow: hidden;
        }
    }
    .epginfoboxes {
        width: 20px !important;
        height: 20px !important;
        background-color: #002eb3 !important;
        border-radius: 4px;
        margin-right: 3px;
    }
    .epginfoboxesless{
        width: 20px !important;
        height: 20px !important;
        background-color: #ff9a00 !important;
        border-radius: 4px;
        margin-right: 3px;
    }
    .type_id {
        display: flex;
        flex-direction: row;
    }

    /*# sourceMappingURL=style.css.map */
</style>

<body>
    <div class="cs-container">
        <div class="cs-invoice cs-style1">
            <div class="cs-invoice_in" id="download_section">
                <div class="cs-invoice_head cs-type1 cs-mb25">
                    <div class="cs-invoice_left">
                        <p class="cs-invoice_number cs-primary_color cs-mb5 cs-f16"><b class="cs-primary_color">Broadcasting
                                No:</b> #SILOSTREAM{{ rand(9999, 99999) }}</p>
                        <p class="cs-invoice_date cs-primary_color cs-m0"><b class="cs-primary_color">Date:
                            </b>{{ date('m-d-Y') }}</p>
                    </div>
                    <div class="cs-invoice_right cs-text_right">
                        <div class="cs-logo cs-mb5">
                            <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAYwAAAB+CAYAAAA3FCbsAAAABGdBTUEAALGOfPtRkwAAACBjSFJNAACHDwAAjA8AAP1SAACBQAAAfXkAAOmLAAA85QAAGcxzPIV3AAAKL2lDQ1BJQ0MgUHJvZmlsZQAASMedlndUVNcWh8+9d3qhzTDSGXqTLjCA9C4gHQRRGGYGGMoAwwxNbIioQEQREQFFkKCAAaOhSKyIYiEoqGAPSBBQYjCKqKhkRtZKfHl57+Xl98e939pn73P32XuftS4AJE8fLi8FlgIgmSfgB3o401eFR9Cx/QAGeIABpgAwWempvkHuwUAkLzcXerrICfyL3gwBSPy+ZejpT6eD/0/SrFS+AADIX8TmbE46S8T5Ik7KFKSK7TMipsYkihlGiZkvSlDEcmKOW+Sln30W2VHM7GQeW8TinFPZyWwx94h4e4aQI2LER8QFGVxOpohvi1gzSZjMFfFbcWwyh5kOAIoktgs4rHgRm4iYxA8OdBHxcgBwpLgvOOYLFnCyBOJDuaSkZvO5cfECui5Lj25qbc2ge3IykzgCgaE/k5XI5LPpLinJqUxeNgCLZ/4sGXFt6aIiW5paW1oamhmZflGo/7r4NyXu7SK9CvjcM4jW94ftr/xS6gBgzIpqs+sPW8x+ADq2AiB3/w+b5iEAJEV9a7/xxXlo4nmJFwhSbYyNMzMzjbgclpG4oL/rfzr8DX3xPSPxdr+Xh+7KiWUKkwR0cd1YKUkpQj49PZXJ4tAN/zzE/zjwr/NYGsiJ5fA5PFFEqGjKuLw4Ubt5bK6Am8Kjc3n/qYn/MOxPWpxrkSj1nwA1yghI3aAC5Oc+gKIQARJ5UNz13/vmgw8F4psXpjqxOPefBf37rnCJ+JHOjfsc5xIYTGcJ+RmLa+JrCdCAACQBFcgDFaABdIEhMANWwBY4AjewAviBYBAO1gIWiAfJgA8yQS7YDApAEdgF9oJKUAPqQSNoASdABzgNLoDL4Dq4Ce6AB2AEjIPnYAa8AfMQBGEhMkSB5CFVSAsygMwgBmQPuUE+UCAUDkVDcRAPEkK50BaoCCqFKqFaqBH6FjoFXYCuQgPQPWgUmoJ+hd7DCEyCqbAyrA0bwwzYCfaGg+E1cBycBufA+fBOuAKug4/B7fAF+Dp8Bx6Bn8OzCECICA1RQwwRBuKC+CERSCzCRzYghUg5Uoe0IF1IL3ILGUGmkXcoDIqCoqMMUbYoT1QIioVKQ21AFaMqUUdR7age1C3UKGoG9QlNRiuhDdA2aC/0KnQcOhNdgC5HN6Db0JfQd9Dj6DcYDIaG0cFYYTwx4ZgEzDpMMeYAphVzHjOAGcPMYrFYeawB1g7rh2ViBdgC7H7sMew57CB2HPsWR8Sp4sxw7rgIHA+XhyvHNeHO4gZxE7h5vBReC2+D98Oz8dn4Enw9vgt/Az+OnydIE3QIdoRgQgJhM6GC0EK4RHhIeEUkEtWJ1sQAIpe4iVhBPE68QhwlviPJkPRJLqRIkpC0k3SEdJ50j/SKTCZrkx3JEWQBeSe5kXyR/Jj8VoIiYSThJcGW2ChRJdEuMSjxQhIvqSXpJLlWMkeyXPKk5A3JaSm8lLaUixRTaoNUldQpqWGpWWmKtKm0n3SydLF0k/RV6UkZrIy2jJsMWyZf5rDMRZkxCkLRoLhQWJQtlHrKJco4FUPVoXpRE6hF1G+o/dQZWRnZZbKhslmyVbJnZEdoCE2b5kVLopXQTtCGaO+XKC9xWsJZsmNJy5LBJXNyinKOchy5QrlWuTty7+Xp8m7yifK75TvkHymgFPQVAhQyFQ4qXFKYVqQq2iqyFAsVTyjeV4KV9JUCldYpHVbqU5pVVlH2UE5V3q98UXlahabiqJKgUqZyVmVKlaJqr8pVLVM9p/qMLkt3oifRK+g99Bk1JTVPNaFarVq/2ry6jnqIep56q/ojDYIGQyNWo0yjW2NGU1XTVzNXs1nzvhZei6EVr7VPq1drTltHO0x7m3aH9qSOnI6XTo5Os85DXbKug26abp3ubT2MHkMvUe+A3k19WN9CP16/Sv+GAWxgacA1OGAwsBS91Hopb2nd0mFDkqGTYYZhs+GoEc3IxyjPqMPohbGmcYTxbuNe408mFiZJJvUmD0xlTFeY5pl2mf5qpm/GMqsyu21ONnc332jeaf5ymcEyzrKDy+5aUCx8LbZZdFt8tLSy5Fu2WE5ZaVpFW1VbDTOoDH9GMeOKNdra2Xqj9WnrdzaWNgKbEza/2BraJto22U4u11nOWV6/fMxO3Y5pV2s3Yk+3j7Y/ZD/ioObAdKhzeOKo4ch2bHCccNJzSnA65vTC2cSZ79zmPOdi47Le5bwr4urhWuja7ybjFuJW6fbYXd09zr3ZfcbDwmOdx3lPtKe3527PYS9lL5ZXo9fMCqsV61f0eJO8g7wrvZ/46Pvwfbp8Yd8Vvnt8H67UWslb2eEH/Lz89vg98tfxT/P/PgAT4B9QFfA00DQwN7A3iBIUFdQU9CbYObgk+EGIbogwpDtUMjQytDF0Lsw1rDRsZJXxqvWrrocrhHPDOyOwEaERDRGzq91W7109HmkRWRA5tEZnTdaaq2sV1iatPRMlGcWMOhmNjg6Lbor+wPRj1jFnY7xiqmNmWC6sfaznbEd2GXuKY8cp5UzE2sWWxk7G2cXtiZuKd4gvj5/munAruS8TPBNqEuYS/RKPJC4khSW1JuOSo5NP8WR4ibyeFJWUrJSBVIPUgtSRNJu0vWkzfG9+QzqUvia9U0AV/Uz1CXWFW4WjGfYZVRlvM0MzT2ZJZ/Gy+rL1s3dkT+S453y9DrWOta47Vy13c+7oeqf1tRugDTEbujdqbMzfOL7JY9PRzYTNiZt/yDPJK817vSVsS1e+cv6m/LGtHlubCyQK+AXD22y31WxHbedu799hvmP/jk+F7MJrRSZF5UUfilnF174y/ariq4WdsTv7SyxLDu7C7OLtGtrtsPtoqXRpTunYHt897WX0ssKy13uj9l4tX1Zes4+wT7hvpMKnonO/5v5d+z9UxlfeqXKuaq1Wqt5RPXeAfWDwoOPBlhrlmqKa94e4h+7WetS212nXlR/GHM44/LQ+tL73a8bXjQ0KDUUNH4/wjowcDTza02jV2Nik1FTSDDcLm6eORR67+Y3rN50thi21rbTWouPguPD4s2+jvx064X2i+yTjZMt3Wt9Vt1HaCtuh9uz2mY74jpHO8M6BUytOdXfZdrV9b/T9kdNqp6vOyJ4pOUs4m3924VzOudnzqeenL8RdGOuO6n5wcdXF2z0BPf2XvC9duex++WKvU++5K3ZXTl+1uXrqGuNax3XL6+19Fn1tP1j80NZv2d9+w+pG503rm10DywfODjoMXrjleuvyba/b1++svDMwFDJ0dzhyeOQu++7kvaR7L+9n3J9/sOkh+mHhI6lH5Y+VHtf9qPdj64jlyJlR19G+J0FPHoyxxp7/lP7Th/H8p+Sn5ROqE42TZpOnp9ynbj5b/Wz8eerz+emCn6V/rn6h++K7Xxx/6ZtZNTP+kv9y4dfiV/Kvjrxe9rp71n/28ZvkN/NzhW/l3x59x3jX+z7s/cR85gfsh4qPeh+7Pnl/eriQvLDwG/eE8/s3BCkeAAAACXBIWXMAAC4jAAAuIwF4pT92AAAAIXRFWHRDcmVhdGlvbiBUaW1lADIwMjQ6MDc6MjcgMTI6NDk6NTYxRGtvAABSs0lEQVR4Xu2dB2AcxdXHd/ZOxaruuMkN2WoOhBYCoSUkGAglhBZCKHIBQ+iEEAKJYwKhJHQIxUUQejAQSgg49BrgAwxYOslFBjeMwQUbWbZ0t/P93+zs7uze3ulOllznJ+3Nm7J99r2Z2dkZxjk3MuE108wvyMsbGs3h+W3xtrUbNhhLDrSsuIzWaDQazXZOWoPxnmmW5BTlnMqZeRIzjL0QlEPhjMFnGK1Y3uMGfyqR2Pjwnuus5RSo0Wg0mu2TUIMx0zQjOxflnGUwczK8fe1QG2ksXISPsTbD4A+3c37Nnmtam0SERqPRaLYrkgzG20XmoPxI3kwYgn1kkA/VYEhjIWQCUpwzY1qb1XrFXmuslTJYo9FoNNsBPoPxZqnZr8DIewuKf5QM8pGidpEkg6+ZxS/+zpqWf0i/RqPRaLZxfAbjo9L8ZxH0U+lNooPaheu3ZSHO4tyatMuq9QuFT6PRaDTbLKZ0jQ+Lc8dCy2duLBRsAxEMJdghJot8Oqd34UX0XkQGajQajWYbxDUYhhk5X0pJBJuiBGptQsE2HvaPjCs0mHlDZe+itz/tXby7HaTRaDSabQ3RJPWWafYqKM77En7RbTZIJ5qigGswRLyUE4bB79jIW/64+0rrGztIo9FoNNsCooZRUJC3H5wOjUUQ20CExSvGwk8EtY3z8sziWEPfkpNkmEaj0Wi2AYTB4BGjRvgCBI2FaiCCBsGOc388vNqFkIVjGAMhP9TQv+SVWN+S0H1rNBqNZutCGAwo8ArhS4NQ9cHaRDrjQajGQuLFiZ+DDNOcHetfesN7/cwSEa7RaDSarRLnpfcA6bpk3RRlB3oGIUio8RAhUUgXlZiljY39e55immbKTWg0Go1my+EYjN7SDcW2BeF63I5zfzxCDIQLIkJiqZnqHw39St+O9eu5twzTaDQazVaCYzAKpCtI6hWlIPzpjAcRjFeNR8iqgdrM980Ie6dpp973NfUvGiTDNBqNRrOFkQaDuz2kQpuiZFgwRvjlT2gcoRoLSZq6h9yVWOlUg+U2Ng3s9XsaWl1EajQajWaLIQ0GC/0K29HdPlTjEYgShBgIF0QEY321mcCKzDSKmWFePXCnno2NO/X6hX6/odFoNFsOp0lKKOKsX3QL0hmIdHWJVPuzw/xRbFjENB9uGtDrrdgg/X5Do9FotgTiS++PSnssgPIeKcMEQl9Lre3qbtXv/fjiVVk44hdA6Lh2Ee53Q+0wGi3xwfaN/PLqlSsXUYhGo9E4jKmuvpwz4yDpJY2xbE5Dw2nSu91TU1P5c9QFzpJeQTyeOLapqWmt9HYax2A0Q3mPkGFSL7tq2ud3Q+1AxR8wB6pfCmqKYO3C9tphqkx4XrkFO6rV4MYNifiq6ytWWOtEiGazUF5eXhqNRkdEDaMPblYuN60WyzJXIEMusCyrXSbTaLYIMBgPQ0f8QnqJBXPqG8qlvN1TU1N1ATTlTdIrSFi8XywW+1p6O41okoLydpqmkrAVtK2hXVTlHYZqLCSZGosgXlQwnvXAdq6I5vaZO29w3zP0aLjdS3V19Z54EG+rqaluys/LXR2NmB8ZEfNFw2TPMSPyWsRkseqqypYxNdVvIN1lI0eOTPq2R6PRbNv43mEQtm4OKmcbT28H4kMMhAsi0sQm7Uo1HsG1vChfzACTsbt3H9R39oLBfQ+VYZouoqqqqhJGYpbJjPdx2c/BlR+N4OCtcaDedvsh9i8FPfI/R0nnluHDh/eyozQazbaOYzDsmoYQPWzdbIeGxhEhGt+Lk65CsHZhY4epxsJFhgXXUpPK2DGoKP2nuaz/f5oH9R0jIjSbBGoKJ6Pm8AGu7k9kUDbk4r6cV1RYMLuysvJ7MqxDBgwYUKQupmnmyiiNRrOFEYaCu6oXpDAQAhGYQnkTqrGQJId4hBoISWiUDEy5GsIQeyiLRGYvLNvpzuZBJf1ljCZLYCxqcTHvh+j7qFOyArnmeW7wGXBvQ/6Zjkw0C+Fr7GgfQ6MR8yXUVH4g/SmBcYj07dN7nbpUV1ZeLqM1Gs0WxqlZ2B84BEv/qvEIRAlCDIQLIoKxau0ifFeBQIFqIJLjUxxBBMGTzGjBvIVDB/xOf/iXHTU1o3fH9bsLou/iwjC8zo3EQQ2xxgFz6mOH1dfHxsM9r76+YcKchoaxCO/HDevnSFpvr+FShJrKEzU1IwZKv0aj2QZJeodB2Lo5TBGnMxBp4kDHTVEewit/gmt5cQEQ5qb24ksgXjO0rH/ss7KdTtAf/mVKlHpY+JuCuHFHLNb4w/r6ptcsy/ImgldAeLy+vvHJ9a0b9kT6x2WwQ3/Ge9woZY1Gsw3i1jAcrR3UqMLv/XioBiJE46czH3byYLztTx0VZjxkCJxgrD/OGM5M9ujCsp1ebx7cf087QhNGdXX1d3C9DpBeCX+/obHxfBgESwakpbm5eUN7InEyxI/tEAkzTqysrKySPo1Gs41h1zCC3WrTGQ9CNRYSL066CuG1C5tw40HIfQSjpD/5CCQITjYe7u9+0ZzIu4uGDaxrGt5fD2wYAjLCIVL0sIxbYSsS0pcRTU1NG7mRuEJ6HVgkwn4lZY1Gs41hGwpua1P7x69snZhAqEeo8fBCgsYitYGQoaHxcothqyEs5dGJOCl4UG3q9B5GpGnx8IG//1y/3whC3WZ9cLP9PSlmRSw273k4vo8qcScOlOIWgbr5ohY1nD4+lEEZQ02aI0eO7EnrYxkEf1RGdTm0HxzjUNoXyTK4Wxk0aFAh7VPut1gGZ8yQIUMKKioqhnT2+mYCrkW+c4x0vDK4W6HOGKNGjepP54Xz69edTdvUM3D06NFldH50PWXwVoP40nt2r8JVuAK9xFWQCt6W3R9P5QYNhOqHEFTe4S+6bcH2S1n8AhnmRSnxtgjHFaTj+ZPjlF/hyAhg+/lCbpi/Hbpw6UwRuIMzpqbqAVwZak5yaWuPD507d+5i6c2KMTXV78JRu9WumFPfsBMJY6qrr+GMDxGhALeD/n37RmHmU6TxNW1xzu5vaGigXlkueJj3YIxfIL2CeNz6NQ2HQB8RFuTn/xa5/QRsf7CMRs63zqyvb5wq/aGQsqiuqDgCmeUUHB011fWzYwTt3DA+xab+k0jwGY2Njc0yPGtIQZscx2caRxmc7Yt99ZVRDquwvGtY/PF169c/9Pnnn7fawempqdl5mGHkXiW9gtbWjec2NzeLHm1jKir2NiKRcdjfj+Gl0R6cB4TeUy2C8zw34rfX18+bYwf7wfq7GVHzTKxGXa/V9Ynl2MorlmHch/v1ggzLGlybQbi3ZzHOjsbWqxHkfKRLx7gAvy9gH3djH7gXIl9t8pfeZPByc3NPgY44Hie0F4J62DGCddgx8rX1r5Ur19y3fPnyb2V41pAB7NEj9wTGzeNxzNRcrn7wat8DbryG83sQ+eu/qd4fqmzKl9641qfjWh8svQ4r8SxdSq0GwmB83KtwNS5KT0eju3dcCK4KhhiMV+Mcxw1BtCcTttcOU2XC8wa3Kf0yUN0+ia4/GC/ipCBJ3j8hBcZxU6wLhjUvn20H7JjgYbsdl+TX0muTsH4yp7HxRenLipqaSjy4bLj04jKz9jkNDTeQXFNT/SmufvbfzHDjPGzjNukTjKmqOtow2b+kVxBPWEOQBysiJnsM3uRJwrhxCbbzN+lLoqqqaj+sezvEXe2QtLRje7ev37Dh9/QOR4Z1CNVSqisrL8Q1/x28aScy8+BLLc7OhoJ8WgakpLKy8rviq3wFXJed4/F4S15e7l24/j+TwelIcINfG4s1/cFRWFRTKyrsgXsQMPApwErPffttyykwdGT4Mgb58Vxcm2sgdlSbwCEa09a1tJxfXFg4A+t0ymBQ7QEFhElQEFdiG0GjnQw3vubMuhgFj3/IkIyBcv4l6irXQiyzQzqCv9Uet86A4m6QAaF01mBgvVOw3r0Q1VcU67DuwVj3ffKICGQa96W3i/D61LPA9SN9clwwxCNoIFRCo2RgytUQFtyf6xdxfsKNhQ35se6BESP6f0tGDr67edCgHfb7DZRkPpGiR4ShBNk58CA9Ul8fu9ZZHGOxOYhEjP2h8J+FmKEi9oCiugTrvgoxE2NB5CATXdijR/4rmTbHwCD1ra6qeBXrXQ9vFsfIBkPR/IsUgwzICijF3fLzct9Hts/EWBARPB+Xw7D9lTxQdMOLCgvew3FkZCwI7OvwoqLC/2bazEKKGwWKO7DirfBm0vREj/9EGIsXOTOybk4j6NhwP55EwePv2FbHxoJAOmaY9yG/ZJyvqdaKc5uKe/ggvBkaC4L9ICcaeQ/X/ygZ0GVgm8fiAs6AqBqLDSgeHO0YC8KJdFWoEFyfQoiBcEFEMDa8KcrG9gcCBXIrKeJTHgGCw+PssOD+PbCWP44ejDPyephzYTguatgxvzImBRu3RQd2HJTT2dLTZeDSUzPXfGVZgCUIlUjVNPNRovsGbofgQaZvSdSmhIzAuV6BlUmJB8Yn40tRonwEy+1Y7kNAUkkP5/T9/LycJ0gpyKBQ8IAWwyDNwhphHzR+jH3djX1ci2LzLXCp+S3YBIWsy26A0cl6OBwoKioNq4rqKyxvYp/UbETvq1ooMAlmXIwa4zFYn2qbaomdOkTQMb+AmsTrkEObL3Ftdi8tLf6T9KYFxukypA/Pc9xYhp+X5XWhb368DhnM2Bfr/VT6MqaioiKvZ2kx8j47WgapzMb+6rA/1DY51Va/tIMVmHER8s2l0peWqqrKOhzjBOn1QG0FP0/BvQPLPVheQ2hwMM9CXP+ZuO8/kv5NBnlxrDRe6ju5OIzFiajFviL9AtEk9UnvImqDE1YcJ+L+CIeQWlX1uzIBjxrScVMUIf3Kjz9K+t1AJQUcVbYdNU4KkvD9E1jL9btbcJISTTiX3wyav5iU6A4DMv50XI1x0uuAnMLvbmlp/f1nn322WoZ1KaRkq6sq/caKG1eiVjJZ+lIS1iSlkMB28CDy57hpzWdxYwOPGiXxuLE0WL2vqak4ghkRaupRc8pKrH9+Q2Pjw8GuxWMqK/fnEXMaEvs6C3BDNFOk/O5kTE0VjFmw5sb/z+JsEh7SD2SAC710zcvJuRlHdZIMcpjXEGusStWLLaxJyoUb73CWuCwWm/e62jaO+5BTXVFxIq4nHb/6zoag83cKmnHkietxRW4KNneMwQEbudGrcY7HyyCH1m9b1g9Ol4egDPeCMX0bYqBTAX8lYRmXqiVeAjW6nfJzc8/GtSGFnWeH+uiwSYpqM7iHfgPFjQ9wsmcG7weuTxTXdRyULF0ftfZDSnZ3511KGFTwwrN1h/Q6rDYs/puGpqb7cR98BoI6EeREzetwHX8pgxy+WN+6odp5F6WCfWTcJIX8fgDy+38gqjU/er93elgzm2MwqERRIJ4Q78d7YqBVVVk44hdAUGIFybWLcL8/Sm5F/ijB0nEFTyZUv8+Rv8KREcA7NKzlBQO5FRkmfF78C/GEddHQhUvTth1uLwwbNqx3UWHhB7g+7rsHhTV4kO5sTySmQ9mG1Qg6TXcYDGTvzwwWP7a+fu6HMiglVOqHEpgLUX3xuNxoaz9gzrx586Q/CWpaMk32FrKLajRWQ3kMg/JIGnqfXkQzI49qS6pCfHfNN2t/tGTJkvXSnwSuD0PJ+ynkyyNlkAD7GYv9+DoBOKQyGFD0D8RiTbVQUIHapAeuR4XJODU9lcgglTbs98hU+yXoeFGafgLXxdf0BWV0Wro2/zE11VRL2d/22cCaTY/FGs/A8foMtgrO9Xs4V1J+wea9tAZDKk0qzbtgfy9++23LUek6F1BhQYzYrH7kyo1nkF9Dm4yohxWUP+6773quwHU8ANexSfpDgRG4Gjrp99Jrw40/Y19/lD6XTA0G7u+euL8vBe8vjudcHA+9u0vCKSngOfHj+qE1UsdJVyFYu1Cxo8Li/craRfqTj0CC4GCc7ZO/gdU8fyDC2UowWILYsTnRyMfLystuWTKsV9bt4dsa9GISDww1dSyxQ3z0xAW5DNdjnhzK/DeiNLlVwtcmLOvgTIwFgYfhIji+Xip4eE5KZywIehBhmGql16EXthcsFUpyjsCPv/QcT5yfzlgQVAtIcP5b6XVhjFMPpyzgja2tGyemMxaEUGKc3Sy9fmDI0xkLgo4X/5dBdGsvBJ6nfaWYBJT39+H4jAV4D8ZiUjpjQTQ2Nr4HzXgiRN/+OgLGgpofPbixrKVl/Qkd9USb09j4BtKi9K/AjCOgiMMKWkZOJIL8laScT+3IWBAw7lfgpMiQejBjAoyyo8OzYszo0TVYEcbVfzwoSExOZSwIZ2e2qpRaM4XeRFRynBqSWVOUjRcViIA/NJhAWMqjE3FS8CFDA8GeP8U5BQPxgOP8zovmFs9dVj7s7Ne6sQ/+1gBlYlR590IOSqUU6ArRUOZ/NXJz5sJ41FMpCKW8redLbs6uzLSrK547emntayLCA/oUrgO9+O4QpHs75IEONsdI2FpsfaayPNYwb97/yci0wDg1ilqTAm5EhRQzwzL+lmlPLhio4BAvRAs0N72M7hA6XjjBe5CmgBE5RQou3Ehc0pFxc5C9+ej7n4yg5i84vmmfcW5TMm12XdfSQgZ1o+0T0FfQx0rZBfkrihvlK1Qgv7yIfJNRd2Myvrgbvu7RYCBqLVn3Mhw9enS5kRP9L47H92IfO7i5vj52pfSGIgwGMpxJP0JSgVYNhHggIk1skoK2sQODUcIvf5LjZEjISinjgL3/kAhaKzxYOv5I4ffFsT6ojt1RUT7kw2WjyoL9lbcroFSWNzQ2Hopi0ClBJRVCNa7P76MRs2FMTdWbKGUd19nST1eBB9/XzJCOqqpRNKVnYHBEa5oUMoJx4xkpOuwrDFEAPJT3z6mPHa8sJ0AhZPwlPXLhMikKuMGy+rDPYiyjTgNEU1NTTIoK/E0ouixmueTU/KaSsgcSM/hhUnRoqK9v8hvijuBGxucXYSz4Tqh11apVD0m5Q+xuwvwt6XVwp4d1oOYyOOL7Iw9ruhQyIhab9zIcX60HhdisDAZ9FJgThbEI5nVu3AfbTjXstPhrGMATXMlGNR6BKEKtXZiUNsrqOWe34UDG4cn9IVLsCgu2C+rUP0S1ZyKeLvpAbG3IptztK3sUuH44yXHebxDv0ILxcivJwdKBEIwjRDD7ToSZLy4fPeypJaOHZfVR0LYElWzmxGIPxBobR8NwnJhUig6F/QBG9bHqqoqPamoqtuiX3ZljBodEaY3HeVbfnnCWCH4R36O6vDzpy/kuwPdiNEUu7RJw/2lfvqYy5IGFUswMzoIftoX2PqSX1zgVd6poAadmk+6DM/9cLzi3V7L+EI8z/31nxi5ScmGMB8Zno121kOLOGCpUQHeeSe8YnIVzHvpBZRiojZTk5ESfgz70NZnhQJ5EwXCCXYtJj20wGLlBFUwhEtVYSJJDDCOvJLK4T2WP9/vvUbSyf3XB4ZVfrjqv4stVdRUrVr06+ouVn1R8sfJTW141bdSyVaesbl9VhhO+FjcN1WO5xeTNirCw/QlEnB/bQNihnrGwSW08KARhycGC8P0jlLGjchmbs2L08OvmV/QLezm4XUCKA4bjn/X1DQcabe2jkcsuR+7q4N0A24UZkZfHVFdfu6VrGx3D/N9bcKOBvmyVvozgPLJIii48J6AAMwCXKkpDQ9Aw86ip7VtVVbWfuiDbdcuwG2kIvjvoULH4YKifZkBeXqRSii6cWRk11XUG6kqLpzrQhMozet/lg/PgfR8SrFkyzoJNtUvq6xevlHLGUO0UtbvbnSUWiyV/NxVCPB43o9HIQzjfYI3kpXg8cRKe74ya/EQvqU/7FOGGKioxaCBUPwQ1lhRw0cD8hUUDcnKYyYaIKM7fKX1uccoXW0Ea+vQZmpNvXg2NcrKzcd8RQHT9qkwIvxToVzhevGogPJlwdyRQt287EKRMqPG25MU7fhi+5Qa3Lr9r3uJ7J+MOiODtHFJsubm5R6FGQS8b6ZsCeVX8yF45p+GypLwueMi6tJcUykt74aHKSOHUVFcvRP5QS14oZfKwl/5pYPRey1/bRA0bx18nfaHgvFlV1aj9GY8cjzx0EC4gvZNIaspKwxtz6huCJVhBWC8pXJfjcV0yHgpnTE01NT8V2T5xL++E4sr4u5wxNVWP4docJ72AN86pjyW964JxPAn5yNccFE9Ye4uX2VmAAkpGQ4PQ+zZqQpVeh+U4vrCJwNLAyID7mnjWt24YSE260kvH9AqOyWuq4sbbyBcdTirWGcJ6SWF/92L/p0ufA7R/W2V9/XzqGZgRyKvUQcpVhxADzzv8YRqAkhUNyls5cLeSb4oH5Y5gEWksCNPM6EWhQ/XKlYtGLf3qlASP740Te0MG22CbqrL2IeKkEELwVDzkFoPx0h88Y3X/4avYEfgfYDJz+tmjh773Vfnw/UTUds78+fMXUUkHD+T+lPlw/6iHRVLvElzDX9HHWNK71YG8Epx7HAqSocSbzRIwFoAznvbDwTGVlT+urqqYLbp12nOmUwkwG2Ox3YACY1INHTV4+qiwW4hErLB3PwOS72tHS/DdF9WW8vz3nXHX4ApY5u9ZuoRkY0EwxnPvoYKa9HeI+U8sttLzcP1JxoP+mZHfMyex03eLV5cMyuuDu1zqrQBoHS6+yM2a0ctWv7/z0q8OYFz0Mpjn7E9FVd5B7MMNiaC1woIlYpvBeOkP7t/GTp9iFWDuwXLM17+qHPno8p0HD5OB2z1UUkGpidpWq5EJ6AWdH2b8kWok0re10S0jgzLOUj6MKAn+2YiYs5Aqqc1bgZpzyABTu7qzZPyCfBsj6d2GaW7IeFyubGEJs9tGu43E48FelIFz48EvuLcMzDgQBbkOa/AOZj9axQFaNaUShBCNmkbvUQXf9C7vETEjzC6RqStIrYxqddZtcyojl371xIZlX49B3flCeO3ByrDp4NHZPvnrj1L8gQhnK8FgSajxINzgYKQdEQwF1A/ghGhufmxl9cgpX26moZi3BlDj+Kwh1jQWYvCFZW5eXk6nx6XqZoLt7NStUgxFsilLqmFMYCyuQF6j+ULUrMPxNws/v05Y/Hvrvm3p0xBrjKD2VoCl2FmQ5k2ZfnujTbouiURed777Cnm3wpfiJ/ReZrNYOTl+g8CZz8+N1AWJboaOz/9SnxmXU01X+tLCHmMst7JXof1yTzUYqgzyS6NGr50LN6Dy4s0foSZQNDYy/Kk9n110v/RuEg29evXOL8y5nJns1zgi+7N/7Mrem7dPe/cy1AsGWMv1q+fnOBCU9KrfTe1z7HhVlpJE3R/h+pfgylzar/EzGl4io5eA2zryi2b6Gtx9OHDiH9bXN+whvT625DuMMTXV1G7tvkxGHr61vj52vvR2KTU1o8YwI4feK6il0GYYiV/EAsNehDGmupoGLFR7n20v7zBo9FYa08ilPZ6o6Wh01iCZvsOoqRm9DzOiNASJh8UPnxOLdXnPrJB3GK8hXyd1v+0KQt9hCPjC9rh1AAr+P0R88Ev75etbN+ymvncJw6SGWiFBq/n0nELxoDzee1Qhz8RYyPDviN8uoHr16lUjl6y4ONHGagzOZ2L7ScrW3n3Y0QeVt0SGBc9Y+FPGyV9/sMALCkb69j8E/ge/rhz55sqqcvpYaKsDD+wZyNjXKMsUGdUp6usXfI5M6ut1gstRI8WtDP6FFCQsi1FEsyWHDJFqLFriCesnmRiL7RnGElS694FCRLc1YVpWJEk5Wqyb7jvjvm9n8CA487JsLpYkLONgGN8l1NMKSjT4jdGAgh75D9BLbekPxeyJJUmrwk8hFIxahVUyOJ+aV7xEanJ1XSki8THYr5pqkylfvnzB8MVfHs8TBr3joPFtZIwf73CC8fIEkoOlAyEYR4QE2347IinODQjGUJxYYV9mGu+uqi6/b2X18K1qmljklCNwfL9Tlsup66GM7iwwGj7yhg0blvUIspsBX08R3Klum/s9+HEaSuyPbsrkS9sLGzcm6KtwHxHGdpdilwPlSaPq+r4xwTPQLfcdCjr4AeTwzTVjIIECyZEokLjfz3z7bct5OKjgIIkHV1VVBKdV9kHjdwvN5uk5z1igVpEo6B31Wxw3IfC0YyDcKF91eFnwC8ouYcSS5W+OWLLi+ygL0PY/sw/B3rl6OITnD0SAlAYCqLbRw05vx4THp94/yb5IeNmpppnTtGpM+e8/Hzlyq5gmljMeHNMmAkKbjzKFGyz4MpkvXrx463jhp4AH+h0pOpShxtXlyoOm4MTtD5YuU45uuiMxf/78L3EnfB8FcmbQu7Bugb49wH33N1ky4wgUdru8lxpL8P9J0SHau6Qk649aUeufXFNTfZOzVFVVZdQ1F/rG10WcxsmyDDF0je99BrTYH5Hvfyi9SZjrsATVH+k2GAsrvzTqfzGjJvRpQ+kSQsZuTXbrV4d1zxfQ9A5g2OdfPmIs/rIKCom6agZeLIYob/dHxNqCRPgD6QVucEh8SJBH8v5VlP0XQb66tCBSv6pm1M9l2BYDmdo39j2B0sIZUswaPHgRnGnQ4CyhB1XKWw2sPREc1oPu4SVS7DKKiorCSpUZFxhEkwHjgSEmth+gwH3jQCH/7J/1+GRZfNgYMpzLQOwv44mhMmV9WxvNN7JWegU8Yp4qxYygWQ5xbn/ANbnAWUyTd3q+94aGhiaL+8dPAxF6j0TD6Uu/DzKl2K8ETwh5eg3vQS+5O1OzcH7I6ZOXx15ec8SwLnufEWSYZW0Y9tmya9vaW0ahmk/9/0MUkVTP6nES7mH6I1w/nPBVQiIQ4F2SpEjEJYc5yYTD2EjTZI+vGTPqpTVV5em6WHYrDXPn0kCD/slvmHHKmKqq4Pg+GVFdUXEaHJ9y4wZPN74TfdTn6zKKWs9mGeRxzty59dibb0wg3JvjaX4M6c2IioqKapQCXxEDvIXQ3t5OSsP38SL2s48UO6SqquJ8rEF9/1VCMti2CefsASk6sEjEFDP9ZQINVQ4n41rJxvZ2evnr67oLhXldTc2IpG8r0oFnZBzu+32pZhSUgz0+bPtsKH+hNJ9xDb6wsAdNnawW4jfG49z/3VqWwGg8hIfyHul1GJiXk3O/KJwEMPN7USESSKVWtFOu0aNvoMuwmh0zMxZOsjIzyt5be8zwSz/cq+ureQ7lS7/5quyzL87lRvsu0DDPplXe4uCkx0H6g8bDxk6fYhWgGguboF8ldP8SGJYfsZzIh2t2qbh7eUVFcOKabkeU/LkR/LjOxBM0Ew/ECdKfEXgQDsJ6SSOaQiGkHHBN9h7zlcJwVXaWQreTsIzg3AK4leaDOJeMRi2goTxyopEXcX8Pys2Jvo/1kuZFkENmB9rq2RHUY0d6UlJTU0k9BUOUJ9/U90xbDVBgb4cY7p9SN2TpTQmudwUzIo9ATFJ0qZg3b94KFGKCw3mjdN3j2aqqqoymacV9mYi8fg8O9NSepSVvpywsxC26d2rXYRp276FUpXkVnBv1bLpceiX8oaampsDzkj3rWlpoqt+PbZ+EGYdUV1bSPPM+zAj31FdeQcQoKetBD62HqtxUTegLd3+E41OYjKrb7NrywcM+WX3MiMNlaLcwdOFXsbLPlh5pcYv6FM+mMPXQgqRU3m5wMNKOCIZ6hBkPL0DsT0H4givQez7DOKMgz5j7zXcqL/qwG9pT0zGnoeFBPEDBUl4BsvajNTXV/0YJ7icoeKQs9dfUlI9GSet2lNJoYLVA8wt/Ggoh/SgA3K9McS2OpFK79HYrsVjsZRxAYBgPVoJzeQkK69JUpUd6L2F/VxElReeUTHvi1k4OK6XhHIOjoWIXUVzbyl+EpSeDhWs/C8aLFFty/30eOsHRtkvcuhi/vpomnp0/I1/di2uR1FFk5MiR+YibhIv4LrxZ1QyI1au/oWlj6fsEF+S73SMm+wD7OxK3JOkhJRA3HPflEdwXKqE792XX3Gg0OFulAMp9AZ4t39wb2PDovNycd2CcQqdcpU4nOLdLcATPwas2XX5jcfEdzyZDhRhutFGB0D8CMTOmiEmiFNg7PVnv4mjxSsql/cYUx3N6KMpAvUyqYvOFuz/C8em/QBwBa/SfeKL9oj5PLUnqEdGVTMFdHjd8wKkoHl+FfYuXjKqBcJV3Sr/8tf9dWUoSrOV5gN8fbIry718i04hfVRZAYkajZfBJPT9uynio7k2FHsCCHnkoqYXOb0ysw338iBl8MZRVC2e8AOfWB+E0rEWKbom8ESX4/aGUk6aJVMHDcQ3OOViyQamc/xf7xLpsAOTX6+tjvolrNvU7DAfqwVVcWPgSjiGsxL8GxzCLcTGXN70sLOXMGIPrcDCOK6i0Vxht7fuFTb4ERUMz+9Fc1GHXagkekvew3VXYD7VZUxfsjrqWtjTEGotlDc3HtvQdhgrywZU49z9IrwrVgt8zqIMGFw/MIMjfD7n+Kh1O0UqFEtQO6YPI4BAxRDP2+RLyeTPjjAzZANyfPbFzqnn6C0/ceLWhsXEs7kXSR4gE1FK0uqqS3tMkTY2AmzcHeekVuEuwnyj2UYl90ERmwdaGODcSx9TXN4VOHR32HUaqKVpVqMAC4+drNsNRLUXNaDcYOzFEi5lrFYsSTWH/XKPTxoJccsLiFIdAmsNyojmfrD1u5E1rjxwednO6BBr8r6x52b0rN1gVuBNTcAGTJ7Z3D089cCcYv/a/D8+fHOM7/wBiH0mr2AHiN2llOz3+KyMGe2XdrpV3NHyHOrV1P9Te2hBrOhaKgSZsCXtBXYzjOgBHR4NFnoFz+xXC6D1Hqj7sb27Y2H5QRxmW4GzjXXCCw0H0wL6Own7GYb+H42HqsArfWai01Z5IHIqHNmxo857Y/wk45z9h+RsWegF5DI4tqKwWQSn/ONVMfVDW6/AAH4uzDWtOoDHZfo7tToBLQ+T4jYX4EtwIfhRbOGrUqJFS3i6A0v0TzjVsGleaiAiKmtXCPR3LIYHr34x8m9HkTipQiA3xhEW9g/zv8GxGYj8Tkf+oMHM9lovs/J9kLGZ9vWrVkamMBYG4+NcrV/0sLH9hmyhwsXOxn+vguRp+mkwqaCzWI2+dlMpYbAr19Y2P4NrR86fABsOQ3ufUsqj+y0gsHpzvnaSIkqiKzBcuf2SYT985cYojcGTGqJnlAiPfnAvDcfbMLAa/ypZdli1rGdy85E9xq41m+aKPVRKhyptIEexEBOO8c8YWA5GhL7olIiZFvB2aFEc7OLssMvqldTVlVJLvdpCxEyhF/gGZc3dk7icpyI7JAo4agcUvQOn3ILvLZMfQx37ItOdCxG63DNQuHIs1wmjwS+H1V9PTw7HOA+3xxJ4wCmm7ysJ4vm9xRgNUZvoVczu2fTUU6U+xm2AXTSMaZaFNGtsqyH8WzrUWuYBmgMuwVx1/YcPGtn2ZFWiPz5DGxsaPce/2kAY5m/zegnvzOxzv4ZnMpUFpkL8OwzpUg0oaqDMl3HjbaI9/L5vaYba0tm6k4ZhEc77CYVVVFWJqYDPKudmjdw4EZpdeVV2lKjVfuPyRYT7d58QpjnBd2RfXF+4dhxw3/MNvjh+Zsu9vVzB04ZdfDFqweCLn7XvgwrsTlwjjkQTC7H9bTiLZQKhk1BQlEf6wjXmruPHYzn4sp2jWmvLyzTYfAim++vqGn+NBHEEPBXItDSqYXFtz4DQbHJ8JQ3HKupaWoXNisVvI+MjYjIChmsYN60iIvqYUyUbOeLLx4ZzGHKMmBXfhnHf48KZCGszrv21ZPwznRA8LtY+HfT9Chm0+nNtgXHfFOqc41feOoGsLY7obrutEbISMQLKSouvJjdvb2uPV2PYVOK64ZYkxunznyrgZWruLJhJ0r3xpsXRY01PBsdE3Ku76qOHRcC8Zg/Wp+Vndf0ZzTpDRoGFhoMR3xUbuRVDYtKnrEfesGNKjPnYoFUy4adH7CHd/2H/GzZJ075DfT0VtYwzWo+lXqZYIMQn6huN/uHeX4fhG4N5cl00+p/uIda7iRuvO2Dq9QyEjF2KkUAvlxuOokR4Gg7Sf3ZsvPTCY9FW5er3fbAdwO0T05mprPwHnRhOluevjnh9Ks/Wx+qKiAX2/W1yfVxzt7dNmqhLzhcsfGebTdU6c4rgu4ST2hUlXwJ4weOKSksc+6/avXpeOGno4alfXQwnbQ1XgOOxDcQWfXwZJVIORbDySXnQr8UKU8W6wz49f+98D8a7fTvtsyceNRyHThWXkboeqp6NGjRocjfL+NOJngkFdmfE1LS3tSzOdCzlTqHujZeWPNE0rwXlkNUqBC3HeKav83Qm928nLyxuO+9sbxggOzc1tfEZNTHaKTYNenvfq1YualkoilkVP+FIosCzn5Nh+Qb4zq3feeWcrJ0c00zDW9nUs1kz5oVs/BC1HAS0ajdJ9L8Z9b4tE4mu6Y790/3v37j0C+yjFviyzvf2rhgULFmA/2dfuuwk2u2/fwcP2zJ/HTOYN16AoPFdTeRrLldVkdpgTYTuuSziJfWGOqwQyYwO04I1tvOWafo9mOVVilrxmmtGKnQdP4MycAuMh28VxLPa/K0tJ4jcW4teLhKx4gGowhKPEq371lxwpiXhXJqQfmersotmxO+1AjUaj6X7Yopo+NSXDCrx5YVWF54ie9nJlNZkd5sUJfLL0hMWH7Y9gxjKY1ct6P7rwfhjYbi1Jz+/Xr6SoZ8GlUPYXYr89vMOAZP9LVGNBBP0IUQJUY0EIUcarMuGG2v8eSOP6Vdkwvtq4PlHeuwv6YWs0Gk0mmJFC5UMpRYG5msnTVq6sJrPDvDiBT5aesPiw/RG2PMhk7L7Vvxj5zrqTRn5fhHQT5V99tXbAvM8vt6w4zRhH3yDAQOEg7P8UZGAsFIQvuILEDsVvMBrpw9cQ9MsrjFLvJI1Go9ksmFPfX0VDCq91lRk5jpZytRUEJ9oNA0L24gQ+WXqcMHJdWUmoiMF4kxl7G6b59rpflj+46rihQ0RgNzFw/pJFO8397BSe4HvjOAKf3CcbCJVgU5RABgVjhF+mD40jgtuDPyStOua/RqPRdCuMc25889NhT0P7HOnTSJ7mcmWfDhOyFyfwydLjhIXFEb5wWryA5P0ZLZyz69fG2/465FExxEK3smLUsJ8ZEZNejFOXXOV4ko3HZmyKEsj9tTbOjpXu3s0v/TQajYYQH+2BOp9G8jSVK7s6jlwhe3ECnyw9TlhYHOELp8ULUJN56VghM40ppTm5DWtP3jmrsY06Q/95n//r67mfj4FNvRDHY08Vi4PxHRsIrV1IREyKeDs0JA7pU2+Rot3YHsN3GbXdjlyq0Wi2LoTBuPk/i5/ihvGBCHF1EQQpu/opJE7gk2WcExaMc/CF0+IFqMm8dBAcmRnDWcR8dN1po15ec3L3ju5abVlt/Zuab96wPjEKtbEbEWRPZysJGgu1duGPkf5AeoG3SnI8/CFruOSwaOj4RhqNRtPViCYp4psjy75rMJM+HspTNZirv1yt5cUJfHIHcQ7BNEqcmsxLByEY7sXRxzJTWza0/HHAo0sz+mBqU/iqYvjOZiRCH/SIYa+zaooifH782v8eiHf9wXVB0EAlNhp9i+vrV0qvRqPRdBuuwSDWHFl2GmMRap7y6yVPgwW0l3QJWiFdnEMwjRKX8T7D42gS/z/Pq59/2+7vd3+b/sqqkWcz0yTD4Y4mm1S7UM9N/Nh+9ZccKYl4VyYC/qCxACuKZscGdHe3Y41GoyGcdxiCns8svo8b/FzoJe8Td0+bKTLwa7L0cQ7BNEqcTxcKWSSQskQG2STF9cRGbigfM+qTdaeP6tSEP9nQJ9b8d1wlGq5dfFyoGosgIth3gpLUq4j0KeMcuPGKNhYajWZz4athOKw+euhhEWY+DI0lxywKaDaf3EGcQzCNEufTpUKWAcFw1w8hGOcEkIMFZ/UcSyQuLL53vm9y/67m65qdD4gYJg1X3EM9BN/5iR/b7/7KaOmIeFUWjvi1Cald0IA0hxfMjtG4QhqNRtPthBoMYtWRg6ui0ZxHkeQ7fs0lXYKUWLo4h2AaJc6nB4UsA4Lhrh9CMM4JIMcf14azu91al7iy56PzA/N+dx2rqsuPZSZ7DKJ3OlISv6osgGT/2wTj4XdlSdBg4Lxm/212bA8axl0GaTQaTbfia5JS6f3M0tj8JYv2gGqiyWzsMZ1UnUUKzOeXLqEqt2AaJc6nA4UsA4Lhrh9CMM4JICc5Lheq96JISbTpm4kV42hSJRHXxfRumP84DK83daY8MfsQ1IMi4Lf/fbh+pE+KS9qGYXErcZ42FhqNZnOSsoahsvKY4YNymHEdNNfJ8EKjBTSeT1Y8wTRKnE8HClkGBMNdP4RgnBNATro4Cfb5gWEkzi+6Z55vzuCugKZSHVG98/tQ7rvKIHkY9gGov+RIScS7MhHwhzVFcYNPKfywgYZE1mg0ms1GRgbDYc2xw/czDfM21Eu+K4MUzQdU5eYLp8UL8OlAIcuAYHi6ODU8XZxE2SeH55H2eOLSXtPnhs2u1WlWVpXvFYmKrsn29FTqOTu/9r+NjFf9rixJbori9/1tduM4XbvQaDSbm6wMBkGz4x1yzPCJ3DSugi7zZn9TFZuq40hW4nz6T8gyIBieLk4NTxcn8e8THtvfSs1I37Jvrxt415L1IqQLWD1m9AOwFlQTc3fs/rq7liDe9fvS2oTULu56bnbsnOOynJAoU2bMmDEMO31Eeg1uWWeOHz/+E+nVdMD0urorccd+QjKeqvfH19aeJyK2A3Bud+LcREER5/Yczu3PImIHYNq0aUeYkcjl0mtMGD9+3x21d2LWBsOB5uPm+eaV0GlnQcl4U6z6NB4tXoBP/wlZBgTD08Wp4eniJP59wuPzi9/FFue/7XnP3Ee7IhN8PWbUqBzGGrEv8b7E3oXcqbp7HIsrEwF/wFi04BgvLvqo4W7p7xamTp1aGYlGY9JLBmM/GIwub77bnMAI9uaMjYW4GzL7YLj58LdCXoi8/3+rTPPlS2prO5wAafr06bvgnlwE0UokEtdPnDiRZpHzMaOujibQFwNCIiO9CKUqjMeW5J66ul3xcNJsllXIUb0MziljfWUxFuPx+H/DziMMXMfXkSn3Fx7O7xs3btzpQt4BwLmPw7lPl14yGOaOajA6/RK45JnPVpc+1nyuZbE94KXp/BRtCEhWlJ5P/wlZBjjh5DpLMI4IhqeLk7j7JEEs0k94cplpmg+vnVT51rqzRu8jwzpN3znz5kEhPSW9HoHd+8CxpYwzjP/GE4lduttYbG/ceeedA6DAp+PafoFr+xCWSyD/EsvPIZ8M9wpmmv9CFXkpFMKNZFjkqqEg7eNY5zQstTCqj8rgrRacz5E4/9lRw5iN870JyxkIPh7HfxyWs/Dg30qFA9Qc3ppaV0dzi2s0HbLJvYZ6zlzwcc/HFh6EUsdJ8NrTSSJ3IlMKkVBEO85Rj064zw3EEcHwdHESd5+OoMT5ZdezDzOjb647u/qaDyeZ7tfbnQElV6Hc7S3jV90fgX26Qb6LYyNrF80owxxb8GH9ISUfN3b7lLXbE1CWB+Tl538KcRwWe6761BTjgtPE959gve/ZQX5oSlo4w2yfYIR0tzqmTJmSD0PxD5zT0/C6HTBSgRPbFzWQ12A4fi+DNJqUdEk3U6qelfyz+ZHWNa2VqKddhcy6QUb59aGQZYAT7nMDcYQTTq6zODhxikO4+3QEJc4vK/G2aDLT+N3oSOVTX542qFCEdIKX6ue/iM0tl16Bu1vs05UlPj+nLsz88q/WtNYUfVT/hAzVZAg1weAaPwexrx0ieN0yjPNgyX/MLWsfyD9BPiUjYdeMCcYGY5kFozFGhrhQ/kZ6t0kC29kqa3tTp07NKRs69EmIp9ghgnU43juxnEDnnjCM/SGfivOh5jNnbnSyiFfDaFwi/RpNKF1iMBx2en5ZS+kjC/5gGHE8dPzpUGNBrhPuc6XHCSOccCcsLE514Lr7VI2Bg09OE2+ywwpKej33+biR+TIkK+RLaZTwsEH7PxwcgxIHpcQf2mgkqgo+bPjLsOZm1+hqMoNqAigtz4DoGHv6cPOX42prD5xQW3vbuHHjXho/fvz/INP7hZspnBQp0jkjEJfinjxw4okneu/kJEh/lpVIVCTi8XJsZ6tUrJFolF5EH2r7BLNgJEbheM/G8hid+8Ta2jch34/z+SXOZ3ekcWuvyIt/gcHcTXo1miS61GA4lDz02YKShxYczQ3rMCjBJlsrBjSnIztxiiNwwp2wsDjV8cX74wSZxss4/B7Qpyj/TuHpBND+/5Wih99AqHzAE3z/oo9iJ/ee3WQ362myBiXsfXF9SQkKYCyugWKkknRKSJFCqVJtw2HXsWPH/lzKPiZMmDB34sSJC6R3q2LatGk1cH5j+8S5v7Z40aIjYSS+lEFJ4Hzq4+3tR0J0ahpRztjVUtZokugWg+FQ8mDz80viC3dB9r0EmlKZO1x1pUcNc8LVMAcnTnV88f44gSNTXEfxDkJkp687r/oo4c+S1jbjNdqGu0XfQQL4EbKCW8aZf/u48XtFn8S26d5IWwOMMd/LW55I3CfFtMyaNeseOCtsH2DseCltM6B2Re8gnJrReiseP3Xy5MmOIUjJGWec0QDjcq/0Up4cO3369G6dClmz7dLpbrXZ8vVxIwbm5kevZaZoX5VFbalEHV3quCQkhQEhywDH8cX74wSOnGQMJMH4pDje9PzyxprjHs3+24d1u1YuglMmPLaBsGGMhl+/LfHNhj+XNjfTsOxbBam61d55551lufn5pyLoBziHAXATyDXNhmU9v27dukcuuOCCpOlyZTfUQ6TXaGPsiUm1tWlf3t9VVzcyl3O3dB+Px5+CQpsnvR0yY8aMv+LauqVslLDzMlGaxPS6uqdwbk7hYO642toKKQum1dWdYHI+lGQ8Mw24LvSexEdnutXefvvt/XoUFp6AB3F/HDu9WO+BHaxESb/eMoxnly1a9CLOIe1HmnfddVev3Ly8LyDmiQDO76RmKCFnAO7V/sw03fc52NmECbW13jsbgGubdbdaMjzIA9Tk9wOsS0YoB9eF5m75GPv4FzWPUbp0YBuHYxvVJFuMLcJx/VNEpEA2qR1s+4x2HOctUg4F1z8vv7DwOJScaX6bcpxbFO4yXP+32zZs+MdZZ521GNvU3WoloobR+/qPLjLPf97ObN1E35kLvyh5YN5pRtzaB2bq/1zN7GhRT5uGhAEhyzgZ3v3GgjArxvav6tRw6VAsHwtBNRYo0BoJa9eijxou3pqMRRiWaTLqPZOXnz8Px38VFroO9EDuCfkEKJkZJaWlTaRwxAoKeMjX4+d6LKTE/5prd+tMC4zFBCc9lmva2tqyuj54yL1aLBgyZMjOUuwQ1EZuhIGcSAtu3BQZ7IIHZZJzbDg36hG4SdB7EiiiKwoKCxfiWt6O7Z6I4O9joZf2P0LYuaguvFA2dOiHSBfae8shmpf3Izju84t8l7YZLsiSJUveheMaVux7k95jUE8t5JsbkD8W4FxuwEKFADqH3bDtH2O5GOf2BgzsG8g7VWKlFIhrLa877sFZMjglMCr7Oemx/EUGh4J97wFj/Sm2+wC8ZOj3xDrfxXI4jvEqyve49pdim/BqCGEwTG78rffAfnN6X/3+z0RoN1L80IJ3b7y/eW8YjQuQM+0vrN3bAcGR1VskZCUO+G5hUOGT68pKQkVMig+LI4H+I0yUGjuBWqKez43E0UWzY2OLPml0S/FbM3ior8HpU5t2usJEGRTDrKDRQMluPhwaJsWG8+OklBrGjpYS8eI555yT3QyKluXtjzDNP8kusR0yYcKE11BrmEYLjv0hGdwtkEIdO3bs0zhfekmt9sajmkSwswMZEFKsZFBCwQnuK0ViozQAGUO1MBjKA7H8UCyJRKff3VFtZ8jQoa/imOgjR7VLcxyLb3pjsB/yzrvTpk0jg7dZuaeubk/s+xUc5ygZFEYerv21UJK/lf4dHjxP4oGipTzC2JP9rn7/pT5T3u3WebJpHKTie+fdYhlxlAaMpXYoDoGOQoouQlbiQIfGwqELjIXkAOlmBeqsS7GPbxk3Llveao0pnj2X+sZvSzjvBB6DJvtJIh4fBIUywEokDsa5UfdNh3wUwh5G9b5E+m04p5KbDWM7U4lO+pK455576MEVTQ8Etp9VKZmYNWvWy3CabJ+4fSdMnT79QVJiMmiroKysjD4opMm3BDjXF0lRL160qMe42toerYYxGNfuYkQ5Q/LnQrndD8WqGgYXnKeq9OZn2gynAkP5Pyyv0kIvw2VwVlCtKTc3dyaOZ28ZRHlgJvLN9194/vl8nFt+e1vbCITRwJmOYSw2I5F/UXOo9Hc7f62rK44axkyIxXaIuAdPUx7fuGHDQJnPD8FxOt3aR0t3h8c0jv8nDCguF/4lP4qY7MOdrnz3zpIp7/SXYd1C6b3NH8Utfiiy/DpXOauukPHjhIEuMRYUl7mxoKRlX50zoEh6M8ZKWM+1tbVWFH0cu7a8qSlYutoWoDdcZ+JBP4G6ok6cOPELKJQvoVBeHl9b+3PETpbp6CINLigouED6BIlEgtqb3elyofRSvkyORqNq7aK1taXlX1LOmEcffTSBY6qF6F5r3MaTcvPyFs6YMeOvd9XV+d5LbAlwHEfiWv1Seumxu2vi+PGHkKJ2FP2va2uXoZZDTWQ08oBTy8phkci99K2F9LtgG+pz6vv+Z3Pyk0MPnYhz82oLnP8B53E88s274t6AM8888zOETaFCB7wtFAaKI9HoNCl3O304p/dc7oeYuH5/RH4+mvL4WWedtVzm8//iOI/FPcj4XdCOgGn0GmmSWmCkGhzX7m0xqYfBmgZMfus35okzO/pattP0um/eHOzYVjyOkk6tuD2E0ifX9qrpfAl94bSkiRNAUJOQjCUvUrqTHZI5vernzenT8Nky6d324Hw6HiTqQRQKDAc1qbxn+wBjE9QmIDx4XyM3eTMCpmuWYsxrDuX83+ecc47vfUSm4CF/B8qISu9qcxZ9X/EbZOLGGXV19dPr6q6B4t070+aqLoWxP0qJ+MSKx89L9QIVSiuGUu+Z0kvZcFQkEnGNjQPC1WatLhtIMxumTJkSRcnT+1qcc1K4V0lfEsg7byMNzbXj8INpdXU/lnK3IQwuY+q7kFdhsFMeJ+4BNc/Rtz0aYBp5K2AwpE+iGI+ezGB/3Wn0wDkDL3+jU91LM2HD+rVTkesDkzRBUB7nUGPhEIxzCKbppLHYUeGc/0OKocgvoG+WXqLsrunTfc2ZyEMdNkvdfffdVEJ2x/HK9qVtEKoBJeLxXXFs1F2U3guoVOOW/g4l2v9NmzZtHozH71DqTzuOVFeB/dBX5HvaPpynZf0FRtWtgYWBUi81/X1g+7AOY7+SoorTnZYuXvB8NwuDBw+mGoPdIxAkGLtSiil54YUXSBm73x2ZnIedW9cSjVIzaz/bAzj/W4c9njjXXd4lphHtacpahU34pRsF5fnU4Mtff3HgZS99R4Z1Gf0eXU7G4qO0ituBPD6/dIl0BiFdnACCmoRkL44b0dzV0rMj0aHy+XbtWqpBuLkGpUxXIRKLFy9+Bo47PW5Ys1QkN5c+HhMdMMA3S5YsSeqymi2iWaG2traN3otwfjuCVtkxCjBguMXXwG2GMr8QpWTnGLoLtZttfM2aNc9KOT2cu0PO43j3C2uW2tKwSEQ9t6/OzGCUY2qmQsahdwk2jNGout1KhHPqiebQtn79+helrMkA04issx8Su0ah1i5EsK0KXPlgWJiPhvzu1TuGXPKsOlbPJoM9yCGm8Ui4iprykBSIbjEWIoEvPGAs6NjqS274NFnhaIzzzz+fur7Kjgviso2UomDy5MkbcP08pRDSLIUM6L6/QNonaR3p3WQm1dY2jRs37lzUOAYkDGMs9k/jQNE3CyrUZHVjWVnZMzfffHMPGdYdqIWtuRdffLHThp8WlIDflyJBw9UEuwt7hl0Oq7+5wX33zo3z2R2W2iXQM+q5DaUX0lLuHhgrlxIx95xzztkW3y1uMUzDLPQyWOAWq8bDkQFVf89mZuG8st+8fJE5qWtKO1DSI0S2cxW1CPMgj88vXSKdQUgX5wSoSUh2/RCEf9OaSLZ7OP9aSkRy845lpWyWuuGGG6j93W275omEW5ruSqjpZ2Jt7SwYj0mLFy0aQsYDuZl6rXm5nrHDS0pK0jbDbRKMqS+nsxkCxmfgTNOkjydV3A8ncTLdafBSw3mnzi3BmO/cStvbg+fWtXDuTfrGebDgoOkAsxdrdV96K0bBRojSLx0lXU+UvW8YUjji07ILZ9FXkp3mgtrLTlhplFYlKW6HLWksDGOtZbC7hKQJhTOmltKSChBLliyhr4jpq3cb03RrGT179qTJjRwlt2Lp0qUvSbnbQA3GIuNBPWOQj3+AIO/YGDtuxowZP5W+roVzVZl3OGmTAwyErxTMOQ9+F6OOF5V154wugbFOnRtLJHznFo1Gu/UDYuDlT8YyquFpPEyL5csmKfErUI2HkGW4Dy99hcnYM8MueOGF4ec+SwOgZYW519ScmDHsrqW8pwyh+ygFYssaCzrPKbo5Kj24VGovnaSXuKSgkV3cj+GQp7z3GKapNkc9hrT0gddmg3pVxdvb6Qt299sFGMDxUuxaGFNrAgVSzARfMw0MSHAoFvpIUoB7UU49lqQ3Y2jCKdT8htDy97//3SuFZwrn6jF1+twSiUTw3LqUQOGmU6NR78iYnOX6X3o7iCAZrhoPKfugcMM4xDCjs0ec9587hkzK/P1GTXXRTYus3r2+5vZnDluTscBZvvKfpfVpx6LRCAZJl/KH2jzlwhMJX7PU1KlTd5eKzSvNW1anm/7ow79pdXWnOwu2XyqjOoQG4INDH/0JcPv3kmLXojSBYB/uNesIy7KGS1EAv+9bC8sw1PcAPYYMGeLreNARdB/y8vObmWkupiWvR4+U3UxTAUWsNu9kfG4wfuq58Xg8nnJ03a4A+dMbZHJL1ca2YUxubBQ1DMcQhBqFgH0gUqQnBXB2TtScO/LXz1yQ7v2GeeLMSMXpM29oZ9Ff0/ZXoZC6ZY2FFLy4+UZL/ITODDq4I0ElUjjuewuLsdDhv+XXwx/ZPtz/aPR4KLb9cLmd0uznEydOfFvKWROJRHZFRq5zFsZYtrVddRiXbvkynHNOswA6VKNUn9HHoFCqalfklqVLl/oGcWSWRT193JoZlH5WQ9mUlZXRkPBek5Jlud14MwU6wDs3xvbMuMcZE1M8Oyw866yz7O71Ht7zx7nXfbiTwLCpc5hX0+CDUtZkAGoYOUnfYTioxkDIMtyX3jYUAic9ll6Ms5tGsgGflJ/19HHDTpzpZkaSR9XOPKa8wHiPGfwiZ/sbuWJbtoix8Byc34J2yzi46K5YaGl5B6LDDg3cNH3vr1BE9I/npKJ8k4H7fjwUmzp22SOZ9qwJA8r4EykKoGSz/QhMLW1m3AafDYlEQu3CmZOTn9/h+Fo4D4aM6jbh4QK9Emy2o6/vEf689BLj5LctGQElqg6maLW1tb0g5YzB9VfPrT8KA86IsSmRytr7wp/z5DlklC7ZuA7ZN5UFgPVRR8hFZapH97yv2k5BDaPdbpJylD2FisdWPrvSESjpfOlV1PSGUQn/Y7ml0XWjJj75+egJTyzKLzK/MRl7AuvZE93I9JbTDX9LGwvDeI2vj3+/502fei9Cd1RMU51YKAmUInNx2WiQORvOG1FLUEtwPqAwqcnJLjEyRl1Dac5tATTgpn6sNxeOW4PB9s+VtZ8OQToyFu5Q7CDrEnYmyLkn3IEBkeP/0FE33qnTp1NtwastWVadlHzwROIacmyfUZyTm3u3MDYdMHXq1KFINEF66R4+T0N6S1/GyHG8Prd9uPymeVVHtYyCgoJz4biGDfkj7NzcbYLR6d6v0PniXNJ+YDxp/Hi6t14NzTQvT3ecQYO9o2P2yAlkKtUYSNlHwNtxeuGPYCc0l0AZ/Dlh6U1qid2yxqIdpaQ//d9H9T/WNQsbXJajZsyYcV3YA0VhZUOH3o407sB3KKnSB3IpoY/pcL/VkqjzwrPhjNpaeyj4TcBKJC6D42TAvlBar0ybNi3t+wgyFkhHX1O7L1+5ZXXfUBCW9QcpESNLSkvvI8Mr/T7EiKqGoY4c+9GSJUtCx9iioTZw4upx/wzGhsaeSvkCmt77mNEofTzoNI21o5Z3uZSzQo7j5Y0tZhjfKysruyWVMsZ9GYsH0X1XgmP/D/JH0ii7eCbVZsooqgR0j5OggQ9xvn/F9Upbs6RaLPZ1o/RSHt99yNChN6fK49jmdRDVaW93aFjfP706xDTyRIlCKHFqFeCWIvuVuxMm0lt2OjdNaHqZhkB8qvSX5j5rHJurFOxwJ126yFi4SZPj/m1ZiYuLb2pwRzrdEYFy8U2g5ML5+3hw78HyMUpccbg1KHqdg0vnjkqKO/npkkWL9pzcwUipUNC/goK+X3oFWJcGf6NxqTYZGLgpuNHqeE0Wjp+aa/6NZU6csW+inOfhHIbiOOjL4pOxqC/In5kwfvzRweaxGXV1VIK2v0Tm/IFx48bRRGA+Mp1AaXpdHRnaX0svbW82fq/DMb35LSguLh4B/y9wHufBdXryrKfBCMePH+9relMh44D79wpEdf6Mxdj+tARjb+CYVpmJRCGLRMqxf1Ks1CTm1XA4vxjn5SpTFVzXDidQotI4FOzj2PYxMojSvoHzumGDab4f2bixNRKJjMZyGrZF86M47yS+TsTje8BgJNXqZcGEOiW4A0fiPP4Bzf9AwjRXYF/FEc73wvaotkpDr6isH1dbq/bgE+A65eA6vQNRfX/yDq7v33Gsc6xIhMbF2tXk/ExsN6nAsUNPoMTjdg1DKHAsuMG2LKIDKJcos/TKCmnTc6OEKR/3qpFhBoFcsYTEEUKWAY7jxHtx7XAeT1jx7xXe8OkRO7qxCAN36w4h4KGBcp1qRiLvQf6QFD6uoDeENU01m0gc15GxINra2qiE7Ov/jvzQZR9GQpFNxoN/PkQnQ5k45sOx3IHltahhzIb7Ls7hMcTRSKSuscD5Prt61aqTulsZwLBegB08KL10fWnSnodxTIuLS0pWQ/4QC83BYBsLztei/n10OmNBQOGub9u4kUrDZDQcyrCtKdDML9O54x6+hXtHU9eSwXOMBdUOLkllLDKFrpsVj9N4ULPsEAAjg/P6F3a0NDcvbxWN4YUwGvzPMRZfYt+HhBkLgrpk49zPgei+/Mbxn4ptzqLzwUbewPbouB1j0WFNFftqxz6Phah+YLgP5Wtcn4+w3fdhMKZhu8JYUL4QKTT0DoOMqUQ8JvJZUZW7I9sxNhSm4qSRS3J66ToE0vdhsnOEulIqYyHckDhCyDLAcZx4O24NN/jvDd5aBkNxXMlNMbVLosaDHv4LcZdotFTvxWMy/8N9/IF8h9AhohcM595cGqi9QFG53xF0BVCst6LEOgbbJsWYSb/+hVBKEyaOH39UpsN1bAr00hr7OgX7JMWpdvNMAtf/2UQisTsNvS2D0jJp0qTVLzz//E+wHr1/6qiLKh4F/rKVSOyDe/A3GbZJkNFavGjRT7H/y7DtdCMO4/SNRzZu2PBd7Nt79xQCnTsKATTVa+rvoWhfnF+Ac7lChqQF+/wceeQHOM7XZFAYq7HN8dBPoc1gOyKs159eHZ7LcxcKJS8KVlKJK81FIi4YRnafwmkjwTgKF2FKk5WMT06PNJCfKrrVGBBR9NKmGAs12pEZlVDYVJ5o+2PRLU3ZzeS2g0ATIBUUFDgT+yTwUFEpnKrwpSwaPQaXkrrB0tAN1MxDSv7fNDJstiXy6XV1N2E79twZnF+E/dwk5G5AntOPOGN7I9+NQIYowj6pl9FXnPN5jLE3Fi9e/C6VZO01wplaV/fDCOeiJxXW+wxGKak32IwZM2jEXTHPAtIsR5pXSU4HvfQuLi4+zDDN/XFN6JuECC7mahTX63Fdn4YCTtmJoCPo3cjgwYMPQal5P2xzBLZPzTPtkJfjWnwKQ/QCth/aDToIzo16PYlRXrHeQqzX4ax+8tofgWu/L/Ztz4cu5/SOo6ZJc2NQWKZQPjSj0ROxrb2xnf5wSaMsxbn8b926dU/RuGb0AWJeXt5BlB73II574I1jloLp06cfhHzwU+SNcmwviu0ttxh7a7VhPH5Jbe06Gr6mV69eNECmAPm1W4av2RZgPf/05sg8bi7wKXC6DfCTK2RC+kWa4LsLkpPS22nUuPD0lpGDxK+VXGNGRKEDdLGx4Mx4mfHEhYU3xdJW6TXdD5RYtGzoUGoKIOVrofhfRhMGiUiNRrNVY+YlYFulB6rV57g4Sl66ik62SZHeXUcGuwTSlxkr1ycZC3KcFV1X2ZIi2jJ+1GhbbuaMH1t045yDtbHYOhg8dCjVYJxvHl7TxkKj2XYwjXg7I6UeVPI+At6O04f4ZVonva3PSTaMMTlL7A/EVGPh4MhhxoJcIeNHjWbGt9js5V9/s76m6MZ6Z15ezRaGerzgNl0qvZQPvOFCNBrNVo/9whvaVf64jk+5S1fguA4p0guZCCR3/Ur6vSIL8zplLAQQvGiOpP/YkLAqim6a85dhM5q7bF4FzaZBX/VSf3fcqn1l0Iq1a9d2We8ojUbT/bD+f3h9lMnNueLdBYVYAaUffP8gX3a78fj3p1feXZAb3J6bHulEnMWfL72Z9TLXq4rfkzM2Fvx/Fk+cX3JLozfHtGaLMn369P0N06ReVqW4TTTTmTsoJXLRhAm1tdOlV6PRbAOYNIOJrcWBUOiqcodrx9hQWJC06aWrEkg/1FyzukNjQY4bJl0SbHmJZVknl9wa21cbi60Lxthg3KKTsdB4U66xwJ2/VxsLjWbbw/85fAoFH1TyLinSu8YjZXoKt6XDcz/O84wAcGTVWDi4MgRmtGLTV7asXlVZcmvDQ939sZWmS/gG+eH3s55/3hu7SKPRbDMIg6EaA1cmVBXshIHU6ZUVFDFVepMZ1pH5H3uf7mdmLGgD/4wneFXxLXMm73TfMj1r1lbK+vXrn4wbxl7csn6Ge39gIh4fNG7cuGvEuEMajWabg/X/7eujIjnG3OR3DXBDwlx/aHr1+wxs3ZUpTk1viXcju0UXL7mr5B/2iKKZGYuPsN4FRbfV05SfGo1Go9mMoJAftxwl7yPg9Sl/R5ZRAgpTcdLIxUtPshCMswtesSd8pwgyFKmNxZeGZUy84baGPbWx0Gg0mi2D2W4ZcUeB+5S7dAWO6+B4lXTuuiLcdlxC0g83v/58l+jiqGssHPzGog3rXt9mxEfDUEybbFEXLY1Go9FsCUyeMJMHllMUvmsIpOsaBRU1zEkjXX96CrelyUXP9ktrLAzjad7WNqbo1vpLe9/SlG4QM41Go9FsBsxVNx64xuBWq6vcyZWRAp/Cl8i04emlqxJI/72cz5qqc5YWhBkLpKi3GB8LQ3F08Z3z5tmhGo1Go9nSON1q/SNiSsWuKnkXRXRR0qVOT+E0FKS15qqiJ8tDjMUqzvn5H86JfbfklgZvPH2NRqPRbBXIoUH4B0LRC9l2BIriV42HKxOOSyhiqvSTi55ZWxppdSZPIWMRR6LbET26+LaGWw982fJNcK/RaDSarQNZw+Av2E5AyYtABcUgCJx0yjo+XK8t7B+d++EhefViXHwBM/7bztu/W3Rrw7nFt9XTOPkajUaj2UoRBqPd+JYMxrdhBiHJKMgoAYUFSZF+J7a2/trSx3eX3vmc8aOLbq0/pNdtc+tlmEaj0Wi2YoTBWHHtUesMbj3sKHmB4zo4XjICMp1rGES47bgo6QuMjfMfLr1n56hhrUX4pcub42OKb214WqbQaDQazTaA89LbSGywroEjJvF3DYF0XaOgooY5aaSrps9n7c2Pl/69V6G58aFW3lpRdHv99eX/btooozUajUazjQDd7in3st+8+BfGjcs6nII16Mq4YPpCY2PTo6V3Luhrrbqi9O9NaSd612g0Gs3WDXQ7lLvEPP/5vDIz8hbj1h5C+VMgub5xoKSsGhUnXklfYrS+Ma247pZd73z9CT2SrEaj0Wz7uE1ShHXLoRtbE61HQbsvEAGk/B2j4Mgy3EU1BXaalii3Jl+y8bmx37nj1ce1sdBoNJrtA+j3ZH0+8IxndsrLjz6JGsM+GdUuCNoQtx7mbfx3cx86cbEdqNFoNJrthVCDQZg/mhIdVr3XBUjwWxiOfo7RCJmCtR3uk4aVuLHpvhPflatrNBqNZjsjpcFwGHbizB6RXvljTWb9yOBGlZGw8k2D06RFzYbF3+bx9bPm3ferFXZqjUaj0WyfGMb/A5aeOuRznvjHAAAAAElFTkSuQmCC" alt="Logo"></div>
                    </div>
                </div>
                <div class="cs-border"></div>
                <ul class="cs-grid_row cs-col_3 cs-mb0 cs-mt20">
                    <li>
                        <p class="cs-mb20"><b class="cs-primary_color">Channel Name:</b> <span class="cs-primary_color">{{ $pdfData['channel_name'] }}</span></p>
                    </li>
                    <li>
                        <p class="cs-mb20"><b class="cs-primary_color">Type Of Channel:</b> <span class="cs-primary_color">Looped</span></p>
                    </li>
                    <li>
                        <p class="cs-mb20"><b class="cs-primary_color">Broadcasting Date:</b> <span class="cs-primary_color">{{ date('m-d-Y') }}</span></p>
                    </li>
                </ul>
                <div class="cs-border cs-mb30"></div>
                <div class="cs-table cs-style1">
                    <div class="cs-round_border">
                        <div class="cs-table_responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th class="cs-width_3 cs-semi_bold cs-primary_color cs-focus_bg">Program Name</th>
                                        <th class="cs-width_3 cs-semi_bold cs-primary_color cs-focus_bg">Description</th>
                                        <th class="cs-width_2 cs-semi_bold cs-primary_color cs-focus_bg">Duration</th>
                                        <th class="cs-width_4 cs-semi_bold cs-primary_color cs-focus_bg">Time Span</th>
                                        <th class="cs-width_3 cs-semi_bold cs-primary_color cs-focus_bg">Type</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pdfData['broadcasts'] as $broadcast)
                                        <tr>
                                            <td class="cs-width_3">{{ $broadcast['title'] }}</td>
                                            <td class="cs-width_3">-</td>
                                            <td class="cs-width_2">{{ $broadcast['duration'] }}</td>
                                            <td class="cs-width_4">{{ $broadcast['since'] }} - {{ $broadcast['till'] }}</td>
                                            <td class="cs-width_3 type_id">
                                                <span>
                                                    <div class="{{ $broadcast['raw_duration'] < 120 ? 'epginfoboxesless' : 'epginfoboxes' }}"></div>
                                                </span>
                                                {{ $broadcast['raw_duration'] < 120 ? 'Less than 2 minutes' : 'Informal' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="cs-invoice_footer">
                        <div class="cs-left_footer cs-mobile_hide"></div>
                        <div class="cs-right_footer">
                            <table>
                                <tbody>
                                    <tr class="cs-border_none">
                                        <td class="cs-width_3 cs-border_top_0 cs-bold cs-f16 cs-primary_color">Total Duration</td>
                                        <td class="cs-width_3 cs-border_top_0 cs-bold cs-f16 cs-primary_color cs-text_right">
                                            {{ $pdfData['total_duration'] }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>        
</body>

</html>