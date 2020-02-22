# Noptin Updates

<p align="center">
    <img src="https://img.shields.io/github/commits-since/hizzle-co/noptin-updates/latest?label=Commits%20To%20Deploy" alt="Commits to deploy">
    <a href="https://travis-ci.org/hizzle-co/noptin-updates">
        <img src="https://img.shields.io/travis/hizzle-co/noptin-updates/master" alt="build status"></a>
    <img src="https://img.shields.io/github/languages/count/hizzle-co/noptin-updates" alt="languages">
    <img src="https://img.shields.io/github/languages/code-size/hizzle-co/noptin-updates" alt="code size">
    <img src="https://img.shields.io/github/repo-size/hizzle-co/noptin-updates" alt="repo size">
    <a href="https://www.gnu.org/licenses/gpl-3.0.en.html">
        <img src="https://img.shields.io/github/license/hizzle-co/noptin-updates" alt="License"></a>
</p>

This plugin allows you to update your Noptin addons and themes from your WordPress admin dashboard.

## Contributing

Contributing isn't just writing code - it's anything that improves the project. All contributions for this plugin are managed right here on GitHub. 

Here are some ways you can help:

### Reporting bugs

If you're running into an issue with the plugin, please use our [issue tracker](https://github.com/hizzle-co/noptin-updates/issues/new) to open a new issue. If you're able, include steps to reproduce, environment information, and screenshots/screencasts as relevant.

### Suggesting enhancements

New features and enhancements are also managed via [issues](https://github.com/hizzle-co/noptin-updates/issues/new).

### Write and submit a patch

If you'd like to fix a bug or make an enhancement, you can submit a Pull Request. 

To do this:-

1. [Fork](https://help.github.com/en/github/getting-started-with-github/fork-a-repo) this repo on GitHub.
2. Make the changes you want to submit.
4. [Create a new pull request](https://help.github.com/en/articles/creating-a-pull-request-from-a-fork).

#### Commit Messages

To ensure that your commit message is included in the CHANGELOG, it should follow [Conventional Commits.](https://www.conventionalcommits.org/en/v1.0.0-beta.2/)

The commit message should be structured as follows:

```
<type>[optional scope]: <description>

[optional body]

[optional footer]
```

`type` can be one of the following.

- feat: A new feature
- fix: A bug fix
- docs: Documentation only changes
- style: Changes that do not affect the meaning of the code (white-space, formatting, missing semi-colons, etc)
- refactor: A code change that neither fixes a bug nor adds a feature
- perf: A code change that improves performance
- test: Adding missing or correcting existing tests
- chore: Changes to the build process or auxiliary tools and libraries such as documentation generation

A scope may be provided to a commit’s type, to provide additional contextual information and is contained within parenthesis, e.g., feat(parser): add ability to parse arrays.

## Release instructions

If you have write access to the repo and you would like to release a new version:-

1. **Merge changes:** Merge all changes into `master` and ensure that [Travis CI](https://travis-ci.org/hizzle-co/noptin-updates) does not produce any fatal errors.
2. **Version bump:** Bump the version numbers in `noptin-updates.php` and `readme.txt` if it does not already reflect the version being released.
4. **Localize:** Update the language files.
5. **Clean:** Check to be sure any new files/paths that are unnecessary in the production version are included in `.gitattributes`.
6. **Test:** Test for functionality locally and ensure everything works as expected.
7. **Release:** Run the following command `npm run release` in the root directory of the plugin.


## Support Level

**Active:** Noptin is actively working on this, and we expect to continue work for the foreseeable future including keeping tested up to the most recent version of WordPress.  Bug reports, feature requests, questions, and pull requests are welcome.
