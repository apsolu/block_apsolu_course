# block_apsolu_course

[![Build Status](https://github.com/apsolu/block_apsolu_course/actions/workflows/moodle-ci.yml/badge.svg?branch=main)](https://github.com/apsolu/block_apsolu_course/actions)
[![Coverage Status](https://coveralls.io/repos/github/apsolu/block_apsolu_course/badge.svg?branch=main)](https://coveralls.io/github/apsolu/block_apsolu_course?branch=main)
[![Moodle Status](https://img.shields.io/badge/moodle-5.0-blue)](https://moodle.org)

## Description

Ce bloc permet d'afficher dans un cours donné :
- pour un étudiant, ses propres présences
- pour un enseignant, le total des présences par session de son cours

Ce bloc donne également à un enseignant des raccourcis pour accéder aux pages de gestion des inscriptions et de prise des présences.


## Installation

```bash
cd /your/moodle/path
git clone https://github.com/apsolu/block_apsolu_course blocks/apsolu_course
php admin/cli/upgrade.php
```


## Reporting security issues

We take security seriously. If you discover a security issue, please bring it
to their attention right away!

Please **DO NOT** file a public issue, instead send your report privately to
[foss-security@univ-rennes2.fr](mailto:foss-security@univ-rennes2.fr).

Security reports are greatly appreciated and we will publicly thank you for it.
