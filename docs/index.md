---
layout: base
title: Welcome to the PHP-ETL Doc page
subTitle: 
---

## What is PHP-ETL

PHP-ETL is the go-to library for executing complex data import, export, and transformation tasks within PHP applications. 
It offers seamless integrations with the [🎵 Symfony Framework](https://symfony.com/), [🦢 Sylius](https://sylius.com/fr/) , and can easily be integrated to 
other CMS and &frameworks, making it ideal for handling intricate data workflows with ease.

## Why PHP-ETL

PHP-ETL was built to address a common challenge in real-world applications: while complex data transformations should 
ideally be handled by enterprise ETL tools or ESBs, the reality is that many CMS platforms require intricate 
transformations within the application itself. This often leads to complex, hard-to-maintain code with limited
flexibility, usually confined to specific execution methods like command-line scripts.

I wanted a more flexible solution—one that allowed easy splitting of code, reusable operations,
consistent logging, and a clear history of processed files. PHP-ETL offers a standardized approach for handling 
complex tasks, such as reading remote files and performing advanced operations like data aggregation,
all while promoting efficient memory usage. 
It provides an abstraction layer for common tasks, simplifying operations across various file systems 
(via [Flysystem](https://flysystem.thephpleague.com/docs/)) and ensuring backup and accessibility of processed files.

Additionally, while PHP isn't naturally suited for asynchronous tasks, 
PHP-ETL handles asynchronous operations—such as API calls—natively, allowing certain tasks to run in parallel, 
like loading data into the database while making API calls. The library also supports visualizing data flows
through auto-generated diagrams, making complex workflows easier to understand and manage.

## A execution tree

{% capture mermaid %}
flowchart TD

subgraph Execution
%% Nodes
0B(Extract Get Article API Params Data<br/><br/>2<i class="sign in alternate icon"></i> / 2<i class="sign out alternate icon"></i><br/>00:00.064<i class="hourglass half icon"></i>)
style 0B fill:#EEE;
1B(Get products/articles until api stop's<br/><br/>2<i class="sign in alternate icon"></i> / 2<i class="sign out alternate icon"></i><br/>00:00.000<i class="hourglass half icon"></i>)@{ shape: hex}
subgraph 1S[Get articles until api stop's]
100B(Make get Article API call<br/><br/>4<i class="sign in alternate icon"></i> / 1<i class="clock icon"></i> / 0<i class="sign out alternate icon"></i><br/>00:05.243<i class="hourglass half icon"></i>)
style 100B fill:#ffe294;
end
style 1B fill:#EEE;
2B(Write api response to file to keep history<br/><br/>4<i class="sign in alternate icon"></i> / 4<i class="sign out alternate icon"></i><br/>00:00.057<i class="hourglass half icon"></i>)
style 2B fill:#EEE;
3B(Split response<br/><br/>5<i class="sign in alternate icon"></i> / 5<i class="sign out alternate icon"></i><br/>00:00.008<i class="hourglass half icon"></i>)
style 3B fill:#EEE;
4B(Map Api fields with Sylius attributes code<br/><br/>2085<i class="sign in alternate icon"></i> / 2085<i class="sign out alternate icon"></i><br/>00:01.482<i class="hourglass half icon"></i>)
style 4B fill:#EEE;
5B(Branch to handle attribute option values & product imports<br/><br/>2085<i class="sign in alternate icon"></i> / 2085<i class="sign out alternate icon"></i><br/>04:28.817<i class="hourglass half icon"></i>)@{ shape: hex}
subgraph 5S[Branch to handle attribute option values & product imports]
500B(Split each attribute items<br/><br/>2085<i class="sign in alternate icon"></i> / 2085<i class="sign out alternate icon"></i><br/>00:00.248<i class="hourglass half icon"></i>)
style 500B fill:#EEE;
501B(Load Attribute from database<br/><br/>89571<i class="sign in alternate icon"></i> / 89571<i class="sign out alternate icon"></i><br/>00:46.995<i class="hourglass half icon"></i>)
style 501B fill:#EEE;
502B(Add new choices to select attributes<br/><br/>89571<i class="sign in alternate icon"></i> / 2<i class="sign out alternate icon"></i><br/>00:09.363<i class="hourglass half icon"></i>)
style 502B fill:#EEE;
503B(Persist attribute<br/><br/>2<i class="sign in alternate icon"></i> / 2<i class="sign out alternate icon"></i><br/>00:00.001<i class="hourglass half icon"></i>)
style 503B fill:#EEE;
510B(Flush Doctrine before importing products<br/><br/>2085<i class="sign in alternate icon"></i> / 2085<i class="sign out alternate icon"></i><br/>00:00.961<i class="hourglass half icon"></i>)
style 510B fill:#EEE;
511B(Load Product from database<br/><br/>2085<i class="sign in alternate icon"></i> / 2085<i class="sign out alternate icon"></i><br/>00:00.904<i class="hourglass half icon"></i>)
style 511B fill:#EEE;
512B(Create or Update product<br/><br/>2085<i class="sign in alternate icon"></i> / 2085<i class="sign out alternate icon"></i><br/>00:27.247<i class="hourglass half icon"></i>)
style 512B fill:#EEE;
513B(Add price to product<br/><br/>2085<i class="sign in alternate icon"></i> / 2085<i class="sign out alternate icon"></i><br/>00:01.651<i class="hourglass half icon"></i>)
style 513B fill:#EEE;
514B(Persist entities<br/><br/>2085<i class="sign in alternate icon"></i> / 2085<i class="sign out alternate icon"></i><br/>00:00.338<i class="hourglass half icon"></i>)
style 514B fill:#EEE;
515B(Flush entities<br/><br/>2085<i class="sign in alternate icon"></i> / 2085<i class="sign out alternate icon"></i><br/>00:02.117<i class="hourglass half icon"></i>)
style 515B fill:#EEE;
516B(Clear doctrine<br/><br/>2085<i class="sign in alternate icon"></i> / 2085<i class="sign out alternate icon"></i><br/>00:00.213<i class="hourglass half icon"></i>)
style 516B fill:#EEE;
517B(Prepare data for Set association product API<br/><br/>2085<i class="sign in alternate icon"></i> / 2085<i class="sign out alternate icon"></i><br/>00:00.201<i class="hourglass half icon"></i>)
style 517B fill:#EEE;
518B(Set Sylius Product ID association - API call<br/><br/>2085<i class="sign in alternate icon"></i> / 2<i class="sign out alternate icon"></i><br/>00:00.687<i class="hourglass half icon"></i>)
style 518B fill:#EEE;
519B(Log association response<br/><br/>2085<i class="sign in alternate icon"></i> / 4168<i class="sign out alternate icon"></i><br/>00:00.012<i class="hourglass half icon"></i>)
style 519B fill:#EEE;
end
style 5B fill:#EEE;
%% Links
0B --> 1B
1B --> 100B
1B --> 2B
1S ~~~ 2B
2B --> 3B
3B --> 4B
4B --> 5B
5B --> 500B
500B --> 501B
501B --> 502B
502B --> 503B
5B --> 510B
510B --> 511B
511B --> 512B
512B --> 513B
513B --> 514B
514B --> 515B
515B --> 516B
516B --> 517B
517B --> 518B
518B --> 519B
end
{% endcapture %}

{% include block/mermaid.html mermaid=mermaid %}


