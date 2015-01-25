<img src="https://raw.githubusercontent.com/amk221/train-up/master/docs/img/logo.png" width="173" height="35">

Train-Up! was for a short time a premium WordPress plugin. As of Febuary 2015 it is now open source. The reasoning for this move is a combination of difficulties in providing support to customers and recent changes to VAT law that affect micro business.

For those who purchased Train-Up! in the last 12 months you can still get support by emailing wptrainup at wptrainup dot co dot uk and providing your licence number.

#### Overview

The plugin was originally designed to make it easy for businesses to train their staff e.g. passing basic fire-safety test.

_Simple example scenario:_

1. Sign up to employer's training website
2. Select a level (e.g. fire safety part 1)
3. Read training resources for that level
4. Take a test on material studied
5. Take one or more resits if necessary
6. View results and communicate with manager for feedback

In terms of features it is similar to [Sensei](http://www.woothemes.com/products/sensei/), [LearnDash](http://www.learndash.com/), [Lifter](https://lifterlms.com/) and [WP Courseware](https://flyplugins.com/wp-courseware/). However, selling your courses is not and never will be a goal of the project. (But, because in Train-Up! everything is 'just a page' you _can_ quite easily still acheive this using existing third party e-commerce plugins).

#### Documentation

The documentation is currently more of an overview of features. Help providing better docs would be greatly appreciated.

* [Levels](docs/levels.md)
* [Resources](docs/resources.md)
* [Tests](docs/tests.md)
* [Questions](docs/questions.md)
* [Groups](docs/groups.md)
* [Trainees](docs/trainees.md)
* [Group managers](docs/group_managers.md)
* [Pages](docs/pages.md)
* [Results](docs/results.md)
* [Archive](docs/archive.md)
* [Bulk emailer](docs/bulk_emailer.md)
* [Theme](docs/theme.md)
* [Settings](docs/settings.md)
* [Tin Can API](docs/tin_can.md)
* [Actions & Filters](docs/actions_and_filters.md)
* [Tips](docs/tips.md)

##### Add-ons
These are simply WordPress themes or plugins that augment Train-Up!'s existing functionality.

* [Simple theme](https://github.com/amk221/train-up.simple_theme)
* [Orderable question type](https://github.com/amk221/train-up.orderable_questions)
* [Fill-in the blanks questions](https://github.com/amk221/train-up.fill_in_the_blanks_questions)
* [Essay style questions](https://github.com/amk221/train-up.essay_questions)
* [File-attachment questions](https://github.com/amk221/train-up.file_attachment_questions)

##### Developer quick-start
To interact with Train-Up! simply access `tu()` in your code. At any one time you will have access to `tu()->post` (the active WordPress post but wrapped with extra training-functionality) and `tu()->user` (the logged in trainee). You can also access the current level, resource, test, question or result post by using the aliases like so: 
`tu()->question->test->level->post_title`