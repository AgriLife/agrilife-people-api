module.exports = (grunt) ->
  @initConfig
    pkg: @file.readJSON('package.json')
    compress:
      main:
        options:
          archive: '<%= pkg.name %>.zip'
        # Pick files to package and release
        files: [
          {src: ['css/*.css']},
          {src: ['**/*.php']},
          {src: ['*.json']},
          {src: ['vendor/*']},
          {src: ['README.md']}
        ]
    gh_release:
      options:
        token: process.env.RELEASE_KEY
        owner: 'agrilife'
        repo: '<%= pkg.name %>'
      release:
        tag_name: '<%= pkg.version %>'
        target_commitish: 'master'
        name: 'Release'
        body: 'First release'
        draft: false
        prerelease: false
        asset:
          name: '<%= pkg.name %>.zip'
          file: '<%= pkg.name %>.zip'
          'Content-Type': 'application/zip'

  @loadNpmTasks 'grunt-contrib-compress'
  @loadNpmTasks 'grunt-gh-release'

  @registerTask 'release', ['compress', 'setreleasemsg', 'gh_release']
  @registerTask 'setreleasemsg', 'Set release message as range of commits', ->
    done = @async()
    grunt.util.spawn {
      cmd: 'git'
      args: [ 'tag' ]
    }, (err, result, code) ->
      if(result.stdout!='')
        # Get last tag in the results
        matches = result.stdout.match(/([^\n]+)$/)
        # Set commit message timeline
        releaserange = matches[1] + '..HEAD'
        grunt.config.set 'releaserange', releaserange
        # Run the next task
        grunt.task.run('shortlog');
      done(err)
      return
    return
  @registerTask 'shortlog', 'Set gh_release body with commit messages since last release', ->
    done = @async()
    grunt.util.spawn {
      cmd: 'git'
      args: ['shortlog', grunt.config.get('releaserange'), '--no-merges']
    }, (err, result, code) ->
      if(result.stdout != '')
        # Hyphenate commit messages
        message = result.stdout.replace(/(\n)\s\s+/g, '$1- ')
        # Set release message
        grunt.config 'gh_release.release.body', message
      else
        # Just in case merges are the only commit
        grunt.config 'gh_release.release.body', 'release'
      done(err)
      return
    return
